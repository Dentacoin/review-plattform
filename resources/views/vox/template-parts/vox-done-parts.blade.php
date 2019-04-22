<div class="section-stats">
	<div class="container">
		<img src="{{ url('new-vox-img/stats-front.png') }}">
		<h3>
			{!! $vox->has_stats && !empty($related_mode) ? trans('vox.page.questionnaire.curious-related-survey', ['title' => $vox->title]) : trans('vox.page.questionnaire.curious-related-surveys') !!}
		</h3>
		<a href="{{ $vox->has_stats && !empty($related_mode) ? $vox->getStatsList() : getLangUrl('dental-survey-stats') }}" class="check-stats">
			{{ trans('vox.common.check-statictics') }}
		</a>
	</div>
</div>

<a href="{{ getLangUrl('profile/invite') }}" class="sticky-invite">
	<div class="sticky-box">
		<img src="{{ url('new-vox-img/invite-icon.png') }}">
		<p>
			{{ $user->is_dentist ? trans('vox.page.questionnaire.invite-patients') : trans('vox.page.questionnaire.invite-friends') }}
		</p>
	</div>
</a>