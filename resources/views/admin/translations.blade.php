@extends('admin')

@section('content')

@if($reloaded)
    <div class="alert alert-success">
        <b>You can do more changes now</b>
    </div>
@endif

<h1 class="page-header">{{ trans('admin.page.'.$current_page.'.title') }}</h1>

@foreach($translations_count_arr as $k => $v)
    <p {!! $all_translations_count > $v ? 'style="color: red;"' : '' !!}> {{ config('langs.'.$k.'.name') }} - {{ $v }}</p>
@endforeach
<!-- end page-header -->

<!-- begin row -->
@if($admin->role!='translator')
<div class="row">
    <!-- begin col-6 -->
    <div class="col-md-4">
        <!-- begin panel -->
        <div class="panel panel-inverse">
            <div class="panel-heading">
                <div class="panel-heading-btn">
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-default" data-click="panel-expand"><i class="fa fa-expand"></i></a>
                </div>
                <h4 class="panel-title">{{ trans('admin.page.'.$current_page.'.choose') }}</h4>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" id="translations-change">
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.choose_from') }}</label>
                        <div class="col-md-9">
                            <select class="form-control" id="translate-from">
                                @foreach($langs as $key => $lang_info)
                                <option value="{{ $key }}" @if($key==$source) selected="selected" @endif >{{ $lang_info['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.choose_to') }}</label>
                        <div class="col-md-9">
                            <select class="form-control" id="translate-to">
                                @foreach($langs as $key => $lang_info)
                                <option value="{{ $key }}" @if($key==$target) selected="selected" @endif >{{ $lang_info['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-sm btn-success">{{ trans('admin.page.'.$current_page.'.choose_submit') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- end panel -->
    </div>
    <!-- end col-6 -->
    <!-- begin col-6 -->
    <div class="col-md-4">
        <!-- begin panel -->
        <div class="panel panel-inverse">
            <div class="panel-heading">
                <div class="panel-heading-btn">
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-default" data-click="panel-expand"><i class="fa fa-expand"></i></a>
                </div>
                <h4 class="panel-title">{{ trans('admin.page.'.$current_page.'.add') }}</h4>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" id="translations-add" method="post" action="{{ url('cms/'.$current_page.'/'.$current_subpage.'/add') }}">
                    {!! csrf_field() !!}
                    <input type="hidden" name="target" value="{{ $target }}" />
                    <input type="hidden" name="source" value="{{ $source }}" />
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.add_key') }}</label>
                        <div class="col-md-9">
                            <input type="text" name="key" value="{{ $request->old('key') }}" class="form-control" placeholder="">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.add_val') }}</label>
                        <div class="col-md-9">
                            <input type="text" name="val" value="{{ $request->old('val') }}" class="form-control" placeholder="">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="submit" name="add" class="btn btn-sm btn-success">{{ trans('admin.page.'.$current_page.'.add_submit') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- end panel -->
    </div>
    <!-- end col-6 -->
    <div class="col-md-4">
        <!-- begin panel -->
        <div class="panel panel-inverse">
            <div class="panel-heading">
                <div class="panel-heading-btn">
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-default" data-click="panel-expand"><i class="fa fa-expand"></i></a>
                </div>
                <h4 class="panel-title">{{ trans('admin.page.'.$current_page.'.export-import') }}</h4>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" id="translations-import" method="post" action="{{ url('cms/'.$current_page.'/'.$current_subpage.'/import/'.$source.'/'.$target) }}" enctype="multipart/form-data">
                    {!! csrf_field() !!}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.export', ['lang' => $langs[$source]['name'] ]) }}</label>
                        <div class="col-md-9">
                            <a target="_blank" href="{{ url('cms/'.$current_page.'/'.$current_subpage.'/export/'.$source.'/'.$target) }}" class="btn btn-sm btn-primary">Export</a>
                            <a target="_blank" href="{{ url('cms/'.$current_page.'/'.$current_subpage.'/export-missing/'.$source.'/'.$target) }}" class="btn btn-sm btn-primary">Export missing</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <i style="color: red;" class="fa fa-exclamation-triangle" aria-hidden="true" title="Select a translation file (previously generated by the system).
Then select the languages ​​that were used when exporting the template.
Check again just in case and click the 'Upload' button."></i>
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.import', ['lang' => $langs[$target]['name'] ]) }}</label>
                        <div class="col-md-9">
                            <input type="file" name="table" accept=".xls, .xlsx" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <button type="submit" name="add" class="btn btn-sm btn-success">{{ trans('admin.page.'.$current_page.'.import-submit') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- end panel -->
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-inverse">
            <div class="panel-heading">
                <div class="panel-heading-btn">
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-default" data-click="panel-expand"><i class="fa fa-expand"></i></a>
                </div>
                <h4 class="panel-title">{{ trans('admin.page.'.$current_page.'.list') }}</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="col-md-12">
                        <input type="text" name="search-translations" id="search-translations" class="form-control" placeholder="{{ trans('admin.page.'.$current_page.'.search') }}">
                    </div>
                    <br/>
                    <br/>
                </div>
                <form class="form-horizontal" id="translations-save" method="post"  action="{{ url('cms/'.$current_page.'/'.$current_subpage.'/update') }}">
                    {!! csrf_field() !!}
                    <input type="hidden" name="target" value="{{ $target }}" />
                    <input type="hidden" name="source" value="{{ $source }}" />
                    <div class="form-group">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 20%">{{ trans('admin.page.'.$current_page.'.list_key') }}</th>
                                    <th style="width: 40%">{{ $langs[$source]['name'] }}</th>
                                    <th style="width: 40%">{{ $langs[$target]['name'] }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($source_arr) && is_array($source_arr))
                                    @foreach( $source_arr as $kgroup => $val)
                                        @if($current_subpage!='validation')
                                        <tr class="info">
                                            <td colspan="3">
                                                <b>{{ $kgroup }}</b>
                                            </td>
                                        </tr>
                                        @endif
                                        @foreach($val as $key => $trans)
                                            <tr>
                                                <td style="width: 20%">{{ $key }} <a href="{{ url('cms/'.$current_page.'/'.$current_subpage.'/'.$source.'/'.$target.'/del/'.$key) }}">{{ trans('admin.page.'.$current_page.'.delete') }}</a></td>
                                                <td style="width: 40%">{!! is_array($trans) ? nl2br(json_encode($trans, JSON_PRETTY_PRINT)) : nl2br($trans) !!}</td>
                                                <td style="width: 40%">
                                                    <textarea class="form-control" name="{{ str_replace('.', '|', $key) }}" placeholder="">{!! isset($target_arr[$key]) ? (is_array($target_arr[$key]) ? json_encode($target_arr[$key], JSON_PRETTY_PRINT) : $target_arr[$key] ) : '' !!}</textarea>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                    <tr class="bnt-tr">
                                        <td colspan="3">
                                            <button type="submit" name="update" class="btn btn-block btn-sm btn-success">
                                                {{ trans('admin.page.'.$current_page.'.list_submit') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if(!empty($reload))
    <script type="text/javascript">
        var should_reload = 1;
    </script>
@endif

@endsection
