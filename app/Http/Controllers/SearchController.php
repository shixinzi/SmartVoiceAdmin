<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use XS;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $key = $request->input('key', '青云志');
        $xs = new XS(config_path('./album.ini'));
        $docs = $xs->search->setSort('score')->setLimit(10)->search($key);
        $count = $xs->search->lastCount;
        $datas = [];
        foreach($docs as $doc) {
            array_push($datas, [
                'albumName' => $doc->albumName,
                'percent' => $doc->percent(),
                'albumVerpic' => $doc->albumVerpic,
                'albumHorpic' => $doc->albumHorpic
            ]);
        }
        return response()->json($datas);
    }
}
