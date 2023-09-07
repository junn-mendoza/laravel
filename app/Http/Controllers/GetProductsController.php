<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DB\Query;
class GetProductsController extends Controller
{
    /**
     * Dummy data for the purpose of the test, normally this would be set by a store builder class
     */
    public int $storeId = 3;
    
    private Query $query;
    public function __construct()
    {
        $this->query = new Query();
    }

    public function __invoke(Request $req)
    {
        return $this->query->build($req, $this->storeId);
    }
    
}
