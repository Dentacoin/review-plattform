@extends('admin')

@section('content')

<h1 class="page-header">{{ trans('admin.page.'.$current_page.'.title') }}</h1>
<!-- end page-header -->


<div class="row">
    <div class="col-md-12">
        <div class="panel panel-inverse">
            <div class="panel-heading">
                <div class="panel-heading-btn">
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-default" data-click="panel-expand"><i class="fa fa-expand"></i></a>
                </div>
                <h4 class="panel-title">{{ trans('admin.page.'.$current_page.'.title') }}</h4>
            </div>
            <div class="panel-body">
        		<div class="panel-body">
					@include('admin.parts.table', [
						'table_id' => 'admins',
						'table_fields' => [
							'id'				=> array('name' => 'ID'),
							'username'			=> array('name' => 'Username'),
							'email'				=> array('name' => 'Email'),
							'comments'			=> array('name' => 'Comments'),
							'update'			=> array('name' => 'Update', 'format' => 'update'),
							'delete'			=> array('name' => 'Delete', 'format' => 'delete'),
						],
                        'table_data' => $admins_list,
						'table_pagination' => false,
                        'pagination_link' => array()
					])
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- begin col-6 -->
    <div class="col-md-12">
        <!-- begin panel -->
        <div class="panel panel-inverse">
            <div class="panel-heading">
                <div class="panel-heading-btn">
                    <a href="javascript:;" class="btn btn-xs btn-icon btn-circle btn-default" data-click="panel-expand"><i class="fa fa-expand"></i></a>
                </div>
                <h4 class="panel-title">{{ trans('admin.page.'.$current_page.'.add') }}</h4>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" id="admin-add" method="post" action="{{ url('cms/admins/add') }}">
                	{!! csrf_field() !!}
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.username') }}</label>
                        <div class="col-md-9">
                            <input class="form-control" type="text" name="username" value="{{ old('username') }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.password') }}</label>
                        <div class="col-md-9">
                            <input class="form-control" type="text" name="password" value="{{ old('password') }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.email') }}</label>
                        <div class="col-md-9">
                            <input class="form-control" type="text" name="email" value="{{ old('email') }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label">{{ trans('admin.page.'.$current_page.'.comments') }}</label>
                        <div class="col-md-9">
                            <textarea class="form-control"  name="comments" value="{{ old('comments') }}"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-3 control-label"></label>
                        <div class="col-md-9">
                            <button type="submit" class="btn btn-sm btn-success">{{ trans('admin.page.'.$current_page.'.submit') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- end panel -->
    </div>
</div>

@endsection