<?php
namespace App\DB;
use Illuminate\Http\Request;
use App\Models\StoreProduct;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class Query {
    private string $imagesDomain;
    const DISABLED_COUNTRY = 'GB';
    public function __construct()
    {
        $this->imagesDomain = "https://img.tmstor.es/";        
    } 
    public function build(Request $req, $store_id )
    {
        $section = $req->section; 
        $number = $req->nummber ?? null;
        $number = (!is_numeric($number) || $number < 1)? 8 : $number;
        $page = $req->page ?? null; 
        $page = (!is_numeric($page) || $page < 1) ? 1 : $page;
        $section = $req->section ?? '%';
        $section_field = is_numeric($section)?'id':'description';
        $section_compare = is_numeric($section)?'=':'LIKE';
        $qry = StoreProduct::select(
            'store_products.id', 'artist_id', 'type',
            'display_name', 'name', 'launch_date', 
            'remove_date', 'store_products.description',
            'available', 'price', 'euro_price', 'dollar_price', 
            'image_format', 'disabled_countries','release_date',
        );
        $product = [];
        $where = [['store_products.store_id',$store_id],
            ['deleted','0'],
            ['available',1]];
        if ($section != '%') {
            $where = Arr::prepend($where, ['store_products.'.$section_field, $section_compare, $section]);
            $qry = $qry->whereHas('sections', function( Builder $q) {
                  $q->with('store_products_section')->orderBy('store_products_section.position');
             })
                ->where( $where )
                ->orderBy('store_products.position')
                ->orderByDesc('release_date');
        } else {
            $qry = $qry->leftjoin('sections','sections.id', '=', DB::raw('-1'))->where( $where )
                ->orderBy('position')
                ->orderByDesc('release_date');
        }      
        if (isset($number) && isset($page) && $page != null) {
            $sp = $qry;
            $page = ($page-1)*$number;
            $product['pages'] = ceil($sp->get()->count() / $number);
            $qry = $qry->offset($page)->take($number);
        } else {
            $qry = $qry->take($number);
        }
        foreach($qry->get() as $row) {
            if ($row->launch_date != null && !isset($_SESSION['preview_mode'])) {
                $launch = strtotime($row->launch_date);
                if ($launch > time()) {
                    continue;
                }
            }
            $row->available = $this->isAvailable($row);
            if($row->available === 1) {
                $product[] = [
                    'image' => strlen($row->image_format) > 2 ? 
                        $this->imagesDomain. "/". $row->id. $row->image_format :
                        $this->imagesDomain."noimage.jpg",    
                    'id' => $row->id,
                    'artist' => $row->artist_id,
                    'title' =>  strlen($row->display_name) > 3 ? $row->display_name : $row->name,
                    'description' => $row->description,
                    'price' => $this->price($row),                       
                    'format' => $row->type,
                    'release_date' => $row->release_date,
                ];
            }
        }
        return $product;
    }

    private function isAvailable(&$row)
    {
        if ($row->remove_date != null) {
            $remove = Carbon::parse($row->remove_date)->timestamp;
            if ($remove < Carbon::now()->toTimeString()) {
                return $row->available = 0;
            }
        }
        //check territories
        if ($row->disabled_countries != '') {
            $countries = explode(',', $row->disabled_countries);
            if (in_array(self::DISABLED_COUNTRY, $countries)) {
                return $row->available = 0;
            }
        }
        return $row->available;
    }

    private function price($row) 
    {
        $price = $row->price;
        switch (session(['currency'])) {
            case "USD":
                $price = $row->dollar_price;
                break;
            case "EUR":
                $price = $row->euro_price;
                break;
        }
        return $price;
    }
} 