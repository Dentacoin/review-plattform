<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Illuminate\Http\Request;

use App\Models\Admin;
use App\Models\Review;
use App\Models\BanAppeal;
use App\Models\DcnTransaction;
use App\Models\TransactionScammersByDay;
use App\Models\TransactionScammersByBalance;

use Auth;
use DB;
use Session;
use Config;

class AdminController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    public $request;
    public $current_page;
    public $current_subpage;
    public $parameters;

    public function __construct(Request $request) {
        
        setlocale(LC_ALL, 'en');
        \App::setLocale('en');

        date_default_timezone_set("Europe/Sofia");

    	$this->request = $request;
    	$path = explode('/', $request->path());
    	$this->current_page = isset($path[1]) ? $path[1] : null;
    	if(!isset( config('admin.pages')[$this->current_page] )) {
			$this->current_page='home';
		}
    	$this->current_subpage = isset($path[2]) ? $path[2] : null;
		if(!isset( config('admin.pages')[$this->current_page]['subpages'][$this->current_subpage] )) {
			$this->current_subpage = isset(config('admin.pages')[$this->current_page]['subpages']) ? key(config('admin.pages')[$this->current_page]['subpages']) : null;
		}
        $this->langs = config('langs');
        
        //$this->user = Auth::guard('web')->user();
        $this->middleware(function ($request, $next) {
            $this->user= Auth::guard('admin')->user();

            return $next($request);
        });

    
        $this->categories = [];
        $clist = config('categories');
        foreach ($clist as $cat) {
            $this->categories[$cat] = trans('admin.categories.'.$cat);
        }
    }

    public function ShowView($page, $params=array()) {


        $params['current_page'] = $this->current_page;
        $params['current_subpage'] = $this->current_subpage;
        $params['request'] = $this->request;
        $params['admin'] = $this->user;
        $params['langs'] = $this->langs;

        $menu = config('admin.pages');
        
        if($params['admin']->role=='translator') {
            foreach ($menu as $key => $value) {
                if($key!='translations' && $key!='export-import' ) {
                    unset($menu[$key]);
                } else {
                    if ($key=='translations') {
                        foreach ($menu[$key]['subpages'] as $sk => $sv) {
                            if(!in_array($sk, explode(',', $params['admin']->text_domain))) {
                                unset( $menu[$key]['subpages'][$sk] );
                            }
                        }
                    }
                }
            }
        }

        if($params['admin']->role=='voxer') {
            foreach ($menu as $key => $value) {
                if($key!='vox' && $key!='users') {
                    unset($menu[$key]);
                } else {
                    if( isset( $menu[$key]['subpages'] ) ) {
                        foreach ($menu[$key]['subpages'] as $sk => $sv) {
                            if($sk=='categories' || $sk=='faq' || $sk=='badges') {
                                unset( $menu[$key]['subpages'][$sk] );
                            }
                        }
                    }
                }
            }
        }
        
        config([
            'admin.pages' => $menu
        ]);
        
        //Counts
        $params['counters'] = [];
        $params['counters']['trp'] = Review::where('youtube_id', '!=', '')->where('youtube_approved', 0)->count();
        $params['counters']['youtube'] = Review::where('youtube_id', '!=', '')->where('youtube_approved', 0)->count();
        $params['counters']['ban_appeals'] = BanAppeal::where('status', 'new')->count();

        $params['counters']['transactions'] = TransactionScammersByDay::where('checked', '!=', 1)->count() ? TransactionScammersByDay::where('checked', '!=', 1)->count() : TransactionScammersByBalance::where('checked', '!=', 1)->count();
        
        $params['cache_version'] = '20210310';

        $params['dcn_warning_transaction'] = DcnTransaction::where('status', 'dont_retry')->count();
        //dd($params['counters']);

        if($this->current_page!='home' && !isset($menu[$this->current_page])) {
            return redirect('cms');
        }


		return view('admin.'.$page, $params);
    }
}