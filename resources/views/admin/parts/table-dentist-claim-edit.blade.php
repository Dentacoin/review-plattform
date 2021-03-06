@if( ( $item->user->status == 'approved' && !empty($item->user->old_unclaimed_profile ) || ($item->user->status != 'approved' && $item->user->status != 'added_by_dentist_claimed' && $item->user->status != 'added_by_clinic_claimed')) && $item->status == 'waiting')
	<a class="btn btn-sm label label-success" href="{{ url('cms/claims/approve/'.$item->id) }}" style="display: block; font-size: 13px; margin-bottom: 5px;">
	    Approve
	</a>
	<a class="btn btn-sm label label-danger" href="{{ url('cms/claims/reject/'.$item->id) }}" style="display: block; font-size: 13px; margin-bottom: 5px;">
	    Reject
	</a>
	<a class="btn btn-sm label label-warning" href="{{ url('cms/claims/suspicious/'.$item->id) }}" style="display: block; font-size: 13px; margin-bottom: 5px;">
	    Suspicious
	</a>
@endif