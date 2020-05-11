<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;

use App\Models\DentistTestimonial;
use Illuminate\Support\Facades\Input;

use Validator;
use Request;
use Excel;
use Image;
use Response;

class TestimonialSliderController extends AdminController
{
    public function list() {

    	$testimonials = DentistTestimonial::orderBy('id', 'desc')->get();

    	return $this->showView('testimonial-slider', [
			'testimonials' => $testimonials,
		]);
    }

    public function add() {

        $validator = Validator::make($this->request->all(), [
            'name-en' => array('required'),
            'image' => array('required'),
        ]);

        if ($validator->fails()) {
            return redirect('cms/testimonial-slider')
            ->withInput()
            ->withErrors($validator);
        } else {
            $newtestimonial = new DentistTestimonial; 
            $newtestimonial->save();

            foreach ($this->langs as $key => $value) {
                if(!empty($this->request->input('name-'.$key))) {
                    $translation = $newtestimonial->translateOrNew($key);
                    $translation->dentist_testimonial_id = $newtestimonial->id;
                    $translation->name = $this->request->input('name-'.$key);
                    $translation->description = $this->request->input('description-'.$key);
                    $translation->job = $this->request->input('job-'.$key);
                }
                $translation->save();
            }

            if( Request::file('image') && Request::file('image')->isValid() ) {
                $img = Image::make( Input::file('image') )->orientate();
                $newtestimonial->addImage($img);
            }

            $this->request->session()->flash('success-message', trans('Testimonial added') );
            return redirect('cms/testimonial-slider');
        }
    }

    public function edit( $id ) {
        $item = DentistTestimonial::find($id);

        if(!empty($item)) {

        	if (Request::isMethod('post')) {
        		$validator = Validator::make($this->request->all(), [
		            'name-en' => array('required'),
	            ]);

	            if ($validator->fails()) {
		            return redirect('cms/testimonial-slider/edit/'.$item->id)
		            ->withInput()
		            ->withErrors($validator);
		        } else {

                    foreach ($this->langs as $key => $value) {
                        if(!empty($this->request->input('name-'.$key))) {
                            $translation = $item->translateOrNew($key);
                            $translation->dentist_testimonial_id = $item->id;
                            $translation->name = $this->request->input('name-'.$key);
                            $translation->description = $this->request->input('description-'.$key);
                            $translation->job = $this->request->input('job-'.$key);
                        }
                        $translation->save();
                    }
		            
		            $item->save();

		            $this->request->session()->flash('success-message', trans('Testimonial edited') );
		            return redirect('cms/testimonial-slider');
		        }
        	}

            return $this->showView('testimonial-slider-edit', array(
                'item' => $item,
            ));
        } else {
            return redirect('cms/testimonial-slider');
        }
    }

    public function add_avatar( $id ) {
        $item = DentistTestimonial::find($id);

        if( Request::file('image') && Request::file('image')->isValid() ) {
            $img = Image::make( Input::file('image') )->orientate();
            $item->addImage($img);

            return Response::json(['success' => true, 'thumb' => $item->getImageUrl(), 'name' => '' ]);
        }
    }



    public function delete( $id ) {
        DentistTestimonial::destroy( $id );

        $this->request->session()->flash('success-message', 'Testimonial deleted' );
        return redirect('cms/testimonial-slider');
    }

}