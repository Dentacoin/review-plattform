@if(!empty($item->user))
	@if($item->user->is_dentist)
		<span class="label label-{{ config('user-statuses-classes')[$item->user->status] }}">{{ config('user-statuses')[$item->user->status] }}</span>
	@else
		@if(!empty($item->user->patient_status))
			<span class="label label-{{ config('user-statuses-classes')[$item->user->patient_status] }}">{{ config('patient-statuses')[$item->user->patient_status] }}</span>
		@endif
	@endif
@else
	@if($item->is_dentist)
		<span class="label label-{{ config('user-statuses-classes')[$item->status] }}">{{ config('user-statuses')[$item->status] }}</span>
	@else
		@if(!empty($item->patient_status))
			<span class="label label-{{ config('user-statuses-classes')[$item->patient_status] }}">{{ config('patient-statuses')[$item->patient_status] }}</span>
		@endif
	@endif
@endif