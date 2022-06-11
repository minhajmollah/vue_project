@extends('front.master.master')

@section('title')
    {{ $seo_info->title }}
@endsection
@php
    use Jenssegers\Agent\Agent as Agent;
    $agent = new Agent();
@endphp
@section('meta')
    <!-- Twitter Card  -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $seo_info->title }}">
    <meta name="twitter:site" content="@shakhawat_fci">
    <meta name="twitter:creator" content="@shakhawat_fci">
    <meta name="twitter:description" content="{{ $seo_info->description }}">
    <meta name="twitter:image" content="{{ url('images/setting/seo/'.$seo_info->meta_image) }}">

    <!-- Open Graph  -->
    <meta property="og:title" content="{{ $seo_info->title }}"/>
    <meta property="og:type" content="Ecommerce Site"/>
    <meta property="og:url" content="{{ url('/') }}"/>
    <meta property="og:image" content="{{ url('images/setting/seo/'.$seo_info->meta_image) }}"/>
    <meta property="og:description" content="{{ $seo_info->description }}"/>

@endsection
@section('content')
    <!-- slider  -->
    @if($shop_info->slider_status == 1)
        @php
            $sliders = homeSlider();
        @endphp
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                    <ol class="carousel-indicators">
                        @if(count($sliders) > 0)
                            @php
                                $data = [];
                                if ($agent->isMobile()) {
                                    foreach ($sliders as $item) {
                                        if ($item->is_mobile) {
                                            $data[] = $item;
                                        }
                                    }
                                }
                                else{
                                    foreach ($sliders as $item) {
                                        if (!$item->is_mobile) {
                                            $data[] = $item;
                                        }
                                    }
                                }
                            @endphp
                            @php $i = 0 @endphp
                           
                            @foreach($data as $slider)
                                <li data-target="#carouselExampleIndicators" data-slide-to="{{ $i }}"
                                    class="@if($loop->first) active @endif"></li>
                                @php $i++ @endphp
                            @endforeach
                        @endif
                    </ol>
                    <div class="carousel-inner">
                        @if(count($sliders) > 0)
                            @php
                                $data = [];
                                if ($agent->isMobile()) {
                                    foreach ($sliders as $item) {
                                        if ($item->is_mobile) {
                                            $data[] = $item;
                                        }
                                    }
                                }
                                else{
                                    foreach ($sliders as $item) {
                                        if (!$item->is_mobile) {
                                            $data[] = $item;
                                        }
                                    }
                                }
                            @endphp
                            @foreach($data as $slider)
                                <div class="carousel-item @if($loop->first) active @endif carousel-border">
                                    <a href="{{ $slider->back_link_url }}"><img class="d-block w-100"
                                                                                src="{{ url('images/slider/'.$slider->slider_image) }}"
                                                                                alt="{{ $slider->title }}"></a>
                                </div>
                            @endforeach
                        @endif
                    </div>
                   
                </div>
            </div>
            </div>
        </div>
    @endif
    <!-- slider  -->
    @php
        $categories = frontCategory();
    @endphp
    <!-- home category from js  -->
    <div class="container">
        <div class="row category">
            <div class="col-md-12">
                <div class="title text-center">
                    <h4>Product Categories</h4>
                </div>
            </div>

        </div>
        <div class="row category home-category">
            <!-- end service 1-->
         
            @foreach($categories as $cat)
                <div class="col-lg-3 col-sm-4 col-4">
                    <a href="{{ route('category.product',['id' => $cat->id,'slug' => str_replace(' ','-',$cat->category_name)]) }}"
                       title="{{ $cat->category_name }}">
                        <div class="">
                            <div class="content">

                                <img style="border-radius:2rem; !important"
                                     src="{{ url('images/category/icon/'.$cat->icon) }}" alt="{{ $cat->category_name }}"
                                     class="img-fluid">
                                <h5 class="text-center font-size">{{ $cat->category_name }}</h5>

                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <!--             <div class="row" v-else>
                        <div class="col-md-12 text-center">
                            <img :src="url+'images/loading.gif'">
                        </div>
                    </div> -->

    </div>
    <!-- <home-category></home-category> -->
    <!-- home category from js  -->

    <!--  end category-->

    <!--start banner-->
    <div class="container">
        <home-offers></home-offers>
    </div>
    <!--end banner-->

    <!--  start hot deal -->
    @php
        $currency = getCurrentCurrency();
    @endphp

    @if($shop_info->hot_deal_status == 1)
        <hot-deal :currency="{{ $currency }}"></hot-deal>
    @endif

    <!--  end   hot deal -->
    @if($shop_info->onsale_status == 1)
        <!-- on slae  -->
        <on-sale-product :currency="{{ $currency }}"></on-sale-product>
        <!-- on slae  -->
    @endif
@endsection

@push('script')
    <script src="{{ asset('public/js/front.js') }}"></script>
@endpush
