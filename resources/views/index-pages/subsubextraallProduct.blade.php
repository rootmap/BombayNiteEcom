@extends('front-layout.master')


@section('content')
<div id="page">
        
        <div class="columns-container">
            <div id="columns" class="container">
                <div id="slider_row" class="row"></div>
                <div class="row" style="padding-top: 15px;">
                    
                    <div id="left_column" class="column col-xs-12 col-sm-12 col-md-3">
                        
                         @include('front-include.categoryfilter')
                         @include('front-include.topseller')
                        
                    </div>
                    @include('front-include.subsuballProduct')
                </div>
            </div>
        </div>
        
    </div>

@endsection
@include('front-include.titleseo')
@include('front-include.index_js')
