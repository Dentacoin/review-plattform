<?php

namespace App\Http\Controllers\Vox;
use App\Http\Controllers\FrontController;

use Validator;
use Response;
use Request;
use Route;
use Hash;
use Auth;
use App;
use Mail;
use DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Vox;
use App\Models\VoxAnswer;
use App\Models\VoxReward;
use App\Models\VoxQuestion;
use App\Models\VoxScale;
use App\Models\UserInvite;
use App\Models\Dcn;
use App\Models\Email;
use App\Models\Reward;


class VoxController extends FrontController
{

    public function __construct(\Illuminate\Http\Request $request, Route $route, $locale=null) {

        parent::__construct($request, $route, $locale);

    	$this->details_fields = config('vox.details_fields');
	}

	public function home($locale=null, $id) {
		$vox = Vox::find($id);
		return $this->dovox($locale, $vox);
	}
	public function home_slug($locale=null, $slug) {
		$vox = Vox::whereTranslationLike('slug', $slug)->first();
		return $this->dovox($locale, $vox);
	}
	public function dovox($locale=null, $vox) {
		
        if(!$this->user->is_verified) {
            return redirect(getLangUrl('welcome-to-dentavox'));
        }
		
		$this->current_page = 'questionnaire';
		$doing_details = false;
		$doing_asl = false;
		$first = Vox::where('type', 'home')->first();

		if(empty($vox) || (!$this->user->is_verified || !$this->user->email) || !$this->user->madeTest($first->id) ) {
			if(!$this->user->is_verified || !$this->user->email) {
            	Request::session()->flash('error-message', 'We\'re currently verifying your profile. Meanwhile you won\'t be able to take surveys or edit your profile. Please be patient, we\'ll send you an email once the procedure is completed.');
			}
			return redirect( getLangUrl('/') );
		} else if( $this->user->madeTest($vox->id) ) {
		    $qtype = Request::input('type');
		    if( 
		    	isset( $this->details_fields[$qtype] ) ||
		    	$qtype=='gender-question' ||
		    	$qtype=='birthyear-question' ||
		    	$qtype=='location-question'
			) {
		    	//I'm doing ASL questions!
				$doing_asl = true;
			} else {
				return redirect( getLangUrl('/') );	
			}
		}

        if($this->user->isBanned('vox')) {
            return redirect(getLangUrl('profile'));
        }


		$list = VoxAnswer::where('vox_id', $vox->id)
		->where('user_id', $this->user->id)
		->orderBy('id', 'ASC')
		->get();
		$answered = [];
		foreach ($list as $l) {
			if(!isset( $answered[$l->question_id] )) {
				$answered[$l->question_id] = $l->answer; //3
			} else {
				if(!is_array($answered[$l->question_id])) {
					$answered[$l->question_id] = [ $answered[$l->question_id] ]; // [3]
				}
				$answered[$l->question_id][] = $l->answer; // [3,5,7]
			}
		}

		$not_bot = !empty($this->admin) || session('not_not-'.$vox->id);


		if(Request::input('goback') && !empty($this->admin)) {
			$this->goBack($answered, $list, $vox);

            return redirect( $vox->getLink() );
		}

		$slist = VoxScale::get();
		$scales = [];
		foreach ($slist as $sitem) {
			$scales[$sitem->id] = $sitem;
		}

        if(Request::isMethod('post')) {
        	$ret = [
        		'success' => true,
        	];
        	if(Request::input('captcha')) {
	            $captcha = false;
	            $cpost = [
	                'secret' => env('CAPTCHA_SECRET'),
	                'response' => Request::input('captcha'),
	                'remoteip' => Request::ip()
	            ];
	            $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
	            curl_setopt($ch, CURLOPT_HEADER, 0);
	            curl_setopt ($ch, CURLOPT_POST, 1);
	            curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($cpost));
	            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    
	            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	            $response = curl_exec($ch);
	            curl_close($ch);
	            if($response) {
	                $api_response = json_decode($response, true);
	                if(!empty($api_response['success'])) {
	                    $captcha = true;
	                }
	            }

	            if(!$captcha) {
	            	$ret['success'] = false;
	            } else {
	            	session([
	            		'not_not-'.$vox->id => true,
	            		'reward-for-'.$vox->id => $vox->getRewardTotal()
	            	]);
	            }

        	} else {

	        	$q = Request::input('question');


	        	if( !isset( $answered[$q] ) && $not_bot ) {

		        	$found = $doing_asl ? true : false;
		        	foreach ($vox->questions as $question) {
		        		if($question->id == $q) {
		        			$found = $question;
		        			break;
		        		}
		        	}

		        	if($found) {
		        		$valid = false;
		        		$type = Request::input('type');

		        		$answer_count = count($question->vox_scale_id && !empty($scales[$question->vox_scale_id]) ? explode(',', $scales[$question->vox_scale_id]->answers) : json_decode($question->answers, true) );

		        		if ($type == 'skip') {
		        			$valid = true;
		        			$a = 0;

		        		} else if ( isset( $this->details_fields[$type] ) ) {

		        			$should_reward = false;
		        			if($this->user->$type==null) {
		        				$should_reard = true;
		        			}

		        			$this->user->$type = Request::input('answer');
		        			$this->user->save();
		        			if( isset( config('vox.stats_scales')[$type] ) ) {
		        				VoxAnswer::where('user_id', $this->user->id)->update([
			        				$type => Request::input('answer')
			        			]);
		        			}
		        			$valid = true;
		        			$a = Request::input('answer');

		        			if( $should_reward ) {
		        				$reward = Reward::where('reward_type', 'vox_question')->first()->dcn;
			        			VoxReward::where('user_id', $this->user->id )->where('vox_id',$vox->id )->update(
			        				array(
			        					'reward' => DB::raw('`reward` + '.$reward
			        				))
			        			);
		        			}

		        		} else if ($type == 'location-question') {
		        			//answer = 71,2312
		        			$country_id = Request::input('answer');
		        			$this->user->country_id = $country_id;
		        			VoxAnswer::where('user_id', $this->user->id)->update([
		        				'country_id' => $country_id
		        			]);
		        			$this->user->save();
		        			$a = $country_id;
		        			$valid = true;
		        		
		        		} else if ($type == 'birthyear-question') {

		        			$this->user->birthyear = Request::input('answer');
		        			$this->user->save();

		        			$agegroup = $this->getAgeGroup(Request::input('answer'));

		        			VoxAnswer::where('user_id', $this->user->id)->update([
		        				'age' => $agegroup
		        			]);

		        			$valid = true;
		        			$a = Request::input('answer');

		        		} else if ($type == 'gender-question') {
		        			$this->user->gender = Request::input('answer');
		        			$this->user->save();
		        			VoxAnswer::where('user_id', $this->user->id)->update([
		        				'gender' => Request::input('answer')
		        			]);
		        			$valid = true;
		        			$a = Request::input('answer');

		        		} else if ($type == 'multiple') {

		        			$valid = true;
		        			$a = Request::input('answer');
		        			foreach ($a as $value) {
		        				if (!($value>=1 && $value<=$answer_count)) {
		        					$valid = false; 
		        					break;
		        				}
		        			}
		        			
		        		} else if($type == 'scale') {
	        				
		        			$valid = true;
		        			$a = Request::input('answer');
		        			foreach ($a as $k => $value) {
		        				if (!($value>=1 && $value<=$answer_count)) {
		        					$valid = false; 
		        					break;
		        				}
		        			}

		        		} else if ($type == 'single') {
	        				$a = intval(Request::input('answer'));
	        				$valid = $a>=1 && $a<=$answer_count;
		        		}



		        		if( $valid ) {
		        			VoxAnswer::where('user_id', $this->user->id )->where('vox_id',$vox->id )->where('question_id', $q)->delete();

		        			$is_scam = false;
					        if($question->is_control) {

					        	if ($question->is_control == '-1') {
			        				if($type == 'single') {
						        		$is_scam = end($answered) != $a;
						        	} else if($type == 'multiple') {
						        		$is_scam = !empty(array_diff( end($answered), $a ));
						        	}
					        	} else {
			        				if($type == 'single') {
					        			$is_scam = $question->is_control!=$a;
						        	} else if($type == 'multiple') {
						        		$is_scam = !empty(array_diff( explode(',', $question->is_control), $a ));
						        	}
					        	}
					        }

				        	if($is_scam) {
				        		
				        		$wrongs = intval(session('wrongs'));
				        		$wrongs++;
				            	session([
				            		'wrongs' => $wrongs
				            	]);

		        				$ret['wrong'] = true;
		        				$prev_bans = $this->user->getPrevBansCount('vox', 'mistakes');

		        				if($wrongs==1 || ($wrongs==2 && !$prev_bans) ) {
		        					$ret['warning'] = true;
		        					$ret['img'] = url('new-vox-img/mistakes'.($prev_bans+1).'.png');
		        					$titles = [
		        						trans('vox.page.bans.warning-mistakes-title-1'),
		        						trans('vox.page.bans.warning-mistakes-title-2'),
		        						trans('vox.page.bans.warning-mistakes-title-3'),
		        						trans('vox.page.bans.warning-mistakes-title-4'),
			        				];
		        					$contents = [
		        						trans('vox.page.bans.warning-mistakes-content-1'),
		        						trans('vox.page.bans.warning-mistakes-content-2'),
		        						trans('vox.page.bans.warning-mistakes-content-3'),
		        						trans('vox.page.bans.warning-mistakes-content-4'),
		        					];
		        					if( $wrongs==2 && !$prev_bans ) {
		        						$ret['zman'] = url('new-vox-img/mistake2.png');
		        						$ret['title'] = trans('vox.page.bans.warning-mistakes-title-1-second');
		        						$ret['content'] = trans('vox.page.bans.warning-mistakes-content-1-second');
		        					} else {
		        						$ret['zman'] = url('new-vox-img/mistake1.png');
		        						$ret['title'] = $titles[$prev_bans];
			        					$ret['content'] = $contents[$prev_bans];
		        					}

		        					if( $wrongs==1 && !$prev_bans ) {
		        						$ret['action'] = 'roll-back';
		        						$ret['go_back'] = $this->goBack($answered, $list, $vox);
		        					} else {
		        						$ret['action'] = 'start-over';
		        						$ret['go_back'] = $vox->questions->first()->id;
										VoxAnswer::where('vox_id', $vox->id)
										->where('user_id', $this->user->id)
										->delete();
		        					}
		        				} else {
					            	session([
					            		'wrongs' => null
					            	]);
	            					$ban = $this->user->banUser('vox', 'mistakes');
	            					$ret['ban'] = true;
	            					$ret['ban_duration'] = $ban['days'];
	            					$ret['ban_times'] = $ban['times'];
		        					$ret['img'] = url('new-vox-img/ban'.($prev_bans+1).'.png');
		        					$titles = [
		        						trans('vox.page.bans.ban-mistakes-title-1'),
		        						trans('vox.page.bans.ban-mistakes-title-2'),
		        						trans('vox.page.bans.ban-mistakes-title-3'),
		        						trans('vox.page.bans.ban-mistakes-title-4', [
		        							'name' => $this->user->getName()
		        						]),
		        					];
		        					$ret['title'] = $titles[$prev_bans];
		        					$contents = [
		        						trans('vox.page.bans.ban-mistakes-content-1'),
		        						trans('vox.page.bans.ban-mistakes-content-2'),
		        						trans('vox.page.bans.ban-mistakes-content-3'),
		        						trans('vox.page.bans.ban-mistakes-content-4'),
		        					];
		        					$ret['content'] = $contents[$prev_bans];

		        					//Delete all answers
									VoxAnswer::where('vox_id', $vox->id)
									->where('user_id', $this->user->id)
									->delete();
		        				}
				        	} else {

			        			if($type == 'single') {

									$answer = new VoxAnswer;
							        $answer->user_id = $this->user->id;
							        $answer->vox_id = $vox->id;
							        $answer->question_id = $q;
							        $answer->answer = $a;
							        $answer->country_id = $this->user->country_id;
							        foreach (config('vox.stats_scales') as $df => $dv) {
							        	if($df=='age') {
		        							$agegroup = $this->getAgeGroup($this->user->birthyear);
		        							$answer->$df = $agegroup;
							        	} else {
							        		if($this->user->$df) {
								        		$answer->$df = $this->user->$df;
								        	}
							        	}
							        }

						        	$answer->save();
							        $answered[$q] = $a;

			        			} else if(isset( $this->details_fields[$type] ) || $type == 'location-question' || $type == 'birthyear-question' || $type == 'gender-question' ) {
			        				$answered[$q] = 1;
			        				$answer = null;
			        			} else if($type == 'skip') {
			        				$answer = new VoxAnswer;
							        $answer->user_id = $this->user->id;
							        $answer->vox_id = $vox->id;
							        $answer->question_id = $q;
							        $answer->answer = 0;
							        $answer->is_skipped = true;
							        $answer->country_id = $this->user->country_id;
							        $answer->save();
							        $answered[$q] = 0;
			        			} else if($type == 'multiple') {
			        				foreach ($a as $value) {
			        					$answer = new VoxAnswer;
								        $answer->user_id = $this->user->id;
								        $answer->vox_id = $vox->id;
								        $answer->question_id = $q;
								        $answer->answer = $value;
								        $answer->country_id = $this->user->country_id;
								        $answer->save();
			        				}
								    $answered[$q] = $a;
			        			} else if($type == 'scale') {
			        				foreach ($a as $k => $value) {
			        					$answer = new VoxAnswer;
								        $answer->user_id = $this->user->id;
								        $answer->vox_id = $vox->id;
								        $answer->question_id = $q;
								        $answer->answer = $k+1;
								        $answer->scale = $value;
								        $answer->country_id = $this->user->country_id;
								        $answer->save();
			        				}
								    $answered[$q] = $a;
			        			}

			        		}



	        				$reallist = $list->filter(function ($value, $key) {
							    return !$value->is_skipped;
							});

	        				$ppp = 5;
		        			if( $reallist->count() && $reallist->count()%$ppp==0 ) {

		        				$pagenum = $reallist->count()/$ppp;
		        				$start = $reallist->forPage($pagenum, $ppp)->first();
		        				
						        $diff = Carbon::now()->diffInSeconds( $start->created_at );
						        $normal = $ppp*3;
						        if($normal > $diff) {

						        	$warned_before = session('too-fast');
						        	if(!$warned_before) {
						        		session([
						            		'too-fast' => true
						            	]);
						        	} else {
						        		session([
						            		'too-fast' => null
						            	]);
						        	}

		        					$prev_bans = $this->user->getPrevBansCount('vox', 'too-fast');
			        				$ret['toofast'] = true;
			        				if(!$warned_before) {
			        					$ret['warning'] = true;
			        					$ret['img'] = url('new-vox-img/ban-warning-fast-'.($prev_bans+1).'.png');
			        					$titles = [
		        							trans('vox.page.bans.warning-too-fast-title-1'),
		        							trans('vox.page.bans.warning-too-fast-title-2'),
		        							trans('vox.page.bans.warning-too-fast-title-3'),
		        							trans('vox.page.bans.warning-too-fast-title-4'),
			        					];
			        					$ret['title'] = $titles[$prev_bans];
			        					$contents = [
		        							trans('vox.page.bans.warning-too-fast-content-1'),
		        							trans('vox.page.bans.warning-too-fast-content-2'),
		        							trans('vox.page.bans.warning-too-fast-content-3'),
		        							trans('vox.page.bans.warning-too-fast-content-4'),
			        					];
			        					$ret['content'] = $contents[$prev_bans];

			        				} else {
		            					$ban = $this->user->banUser('vox', 'too-fast');
		            					$ret['ban'] = true;
		            					$ret['ban_duration'] = $ban['days'];
		            					$ret['ban_times'] = $ban['times'];
			        					$ret['img'] = url('new-vox-img/ban'.($prev_bans+1).'.png');
			        					$titles = [
		        							trans('vox.page.bans.ban-too-fast-title-1'),
		        							trans('vox.page.bans.ban-too-fast-title-2'),
		        							trans('vox.page.bans.ban-too-fast-title-3'),
		        							trans('vox.page.bans.ban-too-fast-title-4',[
		        								'name' => $this->user->getName()
		        							]),
			        					];
			        					$ret['title'] = $titles[$prev_bans];
			        					$contents = [
		        							trans('vox.page.bans.ban-too-fast-content-1'),
		        							trans('vox.page.bans.ban-too-fast-content-2'),
		        							trans('vox.page.bans.ban-too-fast-content-3'),
		        							trans('vox.page.bans.ban-too-fast-content-4'),
			        					];
			        					$ret['content'] = $contents[$prev_bans];

			        					//Delete all answers
										VoxAnswer::where('vox_id', $vox->id)
										->where('user_id', $this->user->id)
										->delete();
			        				}
						        }
		        			}

	        				// dd($answered, count($vox->questions));

					        if(count($answered) == count($vox->questions)) {
								$reward = new VoxReward;
						        $reward->user_id = $this->user->id;
						        $reward->vox_id = $vox->id;
						        $reward->reward = $vox->getRewardForUser($this->user->id);
						        $reward->mistakes = intval(session('wrongs-'.$vox->id));
						        $start = $list->first()->created_at;
						        $diff = Carbon::now()->diffInSeconds( $start );
						        $normal = count($vox->questions)*3;
						        if($normal > $diff) {
						        	$reward->is_scam = true;
						        }
						        $reward->seconds = $diff;

						        $reward->save();
		        				$ret['balance'] = $this->user->getVoxBalance();

		        				if( $reward->is_scam ) {
		        					if($this->user->vox_should_ban()) {
	            						$ret['ban_type'] = $this->user->banUser('vox', 'too-fast');
	            						$ret['ban'] = getLangUrl('profile');
			        				}
		        				} else {

		                            if($this->user->invited_by) {
		                                $inv = UserInvite::where('user_id', $this->user->invited_by)->where('invited_id', $this->user->id)->first();
		                                if(!empty($inv) && !$inv->rewarded) {
		                                    $tmp = Dcn::send($this->user->invitor, $this->user->invitor->my_address(), Reward::getReward('reward_invite'), 'invite-reward', $inv->id, true);
		                                    $inv->rewarded = true;
		                                    $inv->save();

		                                    $this->user->invitor->sendTemplate( 22, [
		                                        'who_joined_name' => $this->user->getName()
		                                    ] );
		                                }
		                            }

		        				}
					        }
		        		} else {
		        			$ret['success'] = false;
		        		}
		        	}
	        	}
        	}

        	return Response::json( $ret );
        }

        $first_question = null;
        $first_question_num = 0;
        if($not_bot) {
        	foreach ($vox->questions as $question) {
	    		$first_question_num++;
	    		if(!isset($answered[$question->id])) {
	    			$first_question = $question->id;
	    			break;
	    		}
	    	}
        } else {
	    	$first_question_num++;
        }


        $total_questions = $vox->questions->count();

        if (!$this->user->birthyear) {
        	$total_questions++;
        }
        if (!$this->user->country_id) {
        	$total_questions++;
        }
        if (!$this->user->gender) {
        	$total_questions++;
        }

        foreach ($this->details_fields as $key => $value) {
        	if($this->user->$key==null) {
        		$total_questions++;		
        	}
        }

        
        $em = new Email;
        $em->user_id = $this->user->id;
        $em->template_id = $this->user->is_dentist ? 27 : 25;
        $em->meta = [
        	'friend_name' => ''
        ];
		list($email_content, $email_title, $email_subtitle, $email_subject) = $em->prepareContent();

		$email_content = preg_replace('#(<a\s.*href=[\'"])(.*?)([\'"].*>)(.*?)(</a>)#', '$2', $email_content);


		$welcomerules = !session('vox-welcome');
		if($welcomerules) {
        	session([
        		'vox-welcome' => true
        	]);
		}

		return $this->ShowVoxView('vox', array(
			'welcomerules' => $welcomerules,
			'not_bot' => $not_bot,
			'details_fields' => $this->details_fields,
			'vox' => $vox,
			'scales' => $scales,
			'answered' => $answered,
			'real_questions' => $vox->questions->count(),
			'total_questions' => $total_questions,
			'first_question' => $first_question,
			'first_question_num' => $first_question_num,
			'js' => [
				'vox.js'
			],
            'canonical' => $vox->getLink(),
            'social_image' => $vox->getSocialImageUrl('survey'),

            'seo_title' => trans('vox.seo.questionnaire.title', [
                'title' => $vox->title,
                'description' => $vox->description
                'stats_description' => $vox->stats_description
            ]),
            'seo_description' => trans('vox.seo.questionnaire.description', [
                'title' => $vox->title,
                'description' => $vox->description
                'stats_description' => $vox->stats_description
            ]),
            'social_title' => trans('vox.social.questionnaire.title', [
                'title' => $vox->title,
                'description' => $vox->description
                'stats_description' => $vox->stats_description
            ]),
            'social_description' => trans('vox.social.questionnaire.description', [
                'title' => $vox->title,
                'description' => $vox->description
                'stats_description' => $vox->stats_description
            ]),

            'email_data' => [
            	'title' => $email_subject,
            	'content' => $email_content,
            ]


        ));
	}

	private function getAgeGroup($by) {

		$years = date('Y') - intval($by);
		$agegroup = 'more';
		if($years<=24) {
			$agegroup = '24';
		} else if($years<=34) {
			$agegroup = '34';
		} else if($years<=44) {
			$agegroup = '44';
		} else if($years<=54) {
			$agegroup = '54';
		} else if($years<=64) {
			$agegroup = '64';
		} else if($years<=74) {
			$agegroup = '74';
		}

		return $years;

	}

	private function goBack($answered, $list, $vox) {

		$lastkey = null;
		if(!empty($answered)) {
			foreach ($list as $aq) {
				if(!$aq->is_skipped) {
					$lastkey = $aq->question_id;
				}
			}
			$found = false;
			foreach ($vox->questions as $question) {
				if($question->id==$lastkey) {
					$found = true;
				}
				if($found) {
					VoxAnswer::where('vox_id', $vox->id)
					->where('user_id', $this->user->id)
					->where('question_id', $question->id)
					->delete();
				}
			}
		}

		return $lastkey;
	}
}