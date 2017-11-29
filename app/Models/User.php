<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

use Illuminate\Support\Str;
use Image;
use App\Models\Email;
use App\Models\Reward;
use App\Models\VoxReward;
use App\Models\VoxCashout;
use App\Models\UserBan;
use Carbon\Carbon;
use Auth;


class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use SoftDeletes, Authenticatable, CanResetPassword;

    protected $fillable = [
    	'email', 
    	'password', 
        'is_dentist',
        'is_partner',
        'title',
        'name',
        'zip',
        'address',
        'phone',
        'website',
        'city_id',
        'country_id',
        'gender',
        'birthyear',
        'avg_rating',
        'ratings',
        'invited_by',
        'hasimage',
        'is_verified',
        'verified_on',
        'verification_code',
        'phone_verified',
        'phone_verified_on',
        'register_reward',
        'register_tx',
        'vox_address',
        'vox_active',
    ];
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'verified_on',
        'phone_verified_on',
    ];

    public function city() {
        return $this->hasOne('App\Models\City', 'id', 'city_id');        
    }
    public function country() {
        return $this->hasOne('App\Models\Country', 'id', 'country_id');        
    }
    public function categories() {
        return $this->hasMany('App\Models\UserCategory', 'user_id', 'id');        
    }
    public function invitor() {
        return $this->hasOne('App\Models\User', 'id', 'invited_by');        
    }
    public function reviews_out() {
        return $this->hasMany('App\Models\Review', 'user_id', 'id')->where('status', 'accepted')->orderBy('id', "DESC");
    }
    public function reviews_in() {
        return $this->hasMany('App\Models\Review', 'dentist_id', 'id')->where('status', 'accepted')->orderBy('verified', "DESC")->orderBy('upvotes', "DESC");
    }
    public function upvotes() {
        return $this->hasMany('App\Models\ReviewUpvote', 'user_id', 'id');
    }
    public function photos() {
        return $this->hasMany('App\Models\UserPhoto', 'user_id', 'id');
    }
    public function bans() {
        return $this->hasMany('App\Models\UserBan', 'user_id', 'id');
    }
    public function invites() {
        return $this->hasMany('App\Models\UserInvite', 'user_id', 'id')->orderBy('id', 'DESC');
    }

    public function getWebsiteUrl() {
        return mb_strpos( $this->website, 'http' )!==false ? $this->website : 'http://'.$this->website;
    }

    public function getName() {
        return $this->title.' '.$this->name;
    }

    public function getNameShort() {
        return explode(' ', $this->name)[0];
    }

    public function getMaskedPhone() {
        return '0'.substr($this->phone, 0, 3).' **** '.substr($this->phone, mb_strlen($this->phone)-2, 2);
    }
    public function getMaskedEmail() {
        $mail_arr = explode('@', $this->email);
        return substr($mail_arr[0], 0, 3).'****@'.$mail_arr[1];
    }

    public function isBanned($domain) {
        foreach ($this->bans as $ban) {
            if($ban->domain==$domain && Carbon::now()->lt( $ban->expires ) ) {
                return $ban;
            }
        }

        return false;
    }

    public function banUser($domain) {
        $times = 0;
        foreach ($this->bans as $ban) {
            if($ban->domain==$domain) {
                $times++;
            }
        }

        if($times==0 || $times==1) {
            $ban = new UserBan;
            $ban->user_id = $this->id;
            $ban->domain = $domain;
            $ban->expires = Carbon::now()->addDays( $times==0 ? 1 : 3 );
            $ban->save();
            $this->sendTemplate(15, [
                'expires' => $ban->expires->toTimeString().' '.$ban->expires->toFormattedDateString()
            ]);

            session([
                'ban-expires' => $ban->expires->toTimeString().' '.$ban->expires->toFormattedDateString()
            ]);
        } else {
            self::destroy( $this->id );
            $this->sendTemplate(16);                                    
        }
        Auth::guard('web')->logout();
    }

    public function hasReviewTo($dentist_id) {
        return Review::where([
            ['user_id', $this->id],
            ['dentist_id', $dentist_id],
        ])->first();
    }

    public function usefulVotesForDenist($dentist_id) {
        $myid = $this->id;
        return Review::where([
            ['dentist_id', $dentist_id],
        ])->whereHas('upvotes', function ($query) use ($myid) {
            $query->where('user_id', $myid);
        })->get()->pluck('id')->toArray();
    }
    
    public function get_invite_token() {
        //dd($this->email.$this->id);
        $token = md5($this->id.date('WY').env('SALT_INVITE'));
        $token = preg_replace("/[^a-zA-Z0-9]/", "", $token);
        return $token;
    }
    public function get_token() {
        //dd($this->email.$this->id);
        $token = md5($this->email.$this->id.date('WY').env('SALT'));
        $token = preg_replace("/[^a-zA-Z0-9]/", "", $token);
        return $token;
    }

    public function sendTemplate($template_id, $params=null) {
        $item = new Email;
        $item->user_id = $this->id;
        $item->template_id = $template_id;
        $item->meta = $params;
        $item->save();
        $item->send();
    }

    public function setNameAttribute($value) {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = $this->makeSlug();
        //
    }

    private function makeSlug() {
        $name = $this->name;
        $i=0;
        $tryval = $name;
        while( self::where('slug', 'LIKE', Str::slug($tryval))->where('id', '!=', $this->id)->first() ) {
            $i++;
            $tryval = $name.$i;
        }
        return Str::slug($tryval);
    }

    public function getLink() {
        return getLangUrl('dentist/'.$this->slug);
    }

    public function parseCategories($categories) {
        return array_intersect_key( $categories, array_flip( array_intersect_key(config('categories'), array_flip( $this->categories->pluck('category_id')->toArray() ) ) ) );
    }

    public function getImageUrl($thumb = false) {
        return $this->hasimage ? url('/storage/avatars/'.($this->id%100).'/'.$this->id.($thumb ? '-thumb' : '').'.jpg') : url('img/no-photo.jpg');
    }
    public function getImagePath($thumb = false) {
        $folder = storage_path().'/app/public/avatars/'.($this->id%100);
        if(!is_dir($folder)) {
            mkdir($folder);
        }
        return $folder.'/'.$this->id.($thumb ? '-thumb' : '').'.jpg';
    }

    public function addImage($img) {

        $to = $this->getImagePath();
        $to_thumb = $this->getImagePath(true);

        $img->resize(1920, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $img->save($to);
        $img->fit( 400, 400 );
        $img->save($to_thumb);
        $this->hasimage = true;
        $this->save();
    }

    public function recalculateRating() {
        $rating = 0;
        foreach ($this->reviews_in as $review) {
            $rating += $review->rating;
        }

        $this->avg_rating = $this->reviews_in->count() ? $rating / $this->reviews_in->count() : 0;
        $this->ratings = $this->reviews_in->count();
        $this->save();
    }

    public function sendSMS($sms_text) {
        $formatted_phone = $this->country->phone_code.$this->phone;
        file_get_contents('https://bulksrv.allterco.net/sendsms/sms.php?nmb_from=1909&user=SWISSDENTAPRIME&pass=m9rr95er9em&nmb_to='.$formatted_phone.'&text='.urlencode($sms_text).'&dlrr=1');
    }

    public function getReviewLimits() {
        if( Auth::guard('admin')->user() ) {
            return null;
        }

        $limits = config('limits.reviews');
        
        if($this->reviews_out->isEmpty()) {
            return null;
        }

        $yearly = 0;
        $monthly = 0;
        foreach ($this->reviews_out as $review) {
            $days = $review->created_at->diffInDays( Carbon::now() );
            if($days>365) {
                break;
            }
            $yearly++;
            if($days>=31) {
                $monthly++;
            }
        }

        if($yearly>=$limits['yearly']) {
            return 'yearly';
        }
        if($monthly>=$limits['monthly']) {
            return 'monthly';
        }
        
        return null;
    }

    public function my_address() {
        $my_dcn_address = $this->register_reward ? $this->register_reward : ( $this->vox_address ? $this->vox_address : null );
        if(!$my_dcn_address) {
            foreach ($this->reviews_out as $ro) {
                if($ro->reward_address) {
                    $my_dcn_address = $ro->reward_address;
                    break;
                }
            }
        }

        return $my_dcn_address;        
    }

    public function canIuseAddress( $address ) {
        $used = self::where('register_reward', 'LIKE', $address)->first();
        if($used && $used->id!=$this->id) {
            return false;
        }

        $used_vox = self::where('vox_address', 'LIKE', $address)->first();
        if($used_vox && $used_vox->id!=$this->id) {
            return false;
        }

        $used_reward = Review::where('reward_address', 'LIKE', $address)->first();
        if($used_reward && $used_reward->user_id!=$this->id) {
            return false;
        }

        return true;
    }

    public static function getBalance($address) {

        $ret = [
            'success' => false
        ];
        $curl = file_get_contents('https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=0x08d32b0da63e2C3bcF8019c9c5d849d7a9d791e6&address='.$address.'&tag=latest&apikey=NBI9SGSW6P1NZQGYT8BD8DDN5UQ7AIM4E9');
        if(!empty($curl)) {
            $curl = json_decode($curl, true);
            if($curl['status']) {
                $ret['success'] = true;
                $ret['result'] = $curl['result'];
            }
        }

        return $ret;
    }

    //
    //
    // Vox 
    //
    //

    public function getVoxBalance() {
        $income = VoxReward::where('user_id', $this->id)->sum('reward');
        $cashouts = VoxCashout::where('user_id', $this->id)->sum('reward');

        return $income - $cashouts;
    }

    public function madeTest($id) {
        return VoxReward::where('user_id', $this->id)
        ->where('vox_id', $id)
        ->first();
    }

    public function vox_cashouts() {
        return $this->hasMany('App\Models\VoxCashout', 'user_id', 'id')->orderBy('id', 'DESC');
    }
    public function vox_rewards() {
        return $this->hasMany('App\Models\VoxReward', 'user_id', 'id')->orderBy('id', 'DESC');
    }

    public function vox_should_ban() {
        $tests = VoxReward::where('user_id', $this->id)
        ->where('is_scam', '1')
        ->where('created_at', '>', Carbon::now()->subDays(7))
        ->count();


        $answers = VoxAnswer::where('user_id', $this->id)
        ->where('is_scam', '1')
        ->where('created_at', '>', Carbon::now()->subDays(7))
        ->count();

        return $tests>=3 || $answers>=3;

    }

}