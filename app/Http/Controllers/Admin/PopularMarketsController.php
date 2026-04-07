<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legacy\PopularMarket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PopularMarketsController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('Record.limit') ?: Session::get('popular_markets_limit', 25);
        if ($request->has('Record.limit')) {
            Session::put('popular_markets_limit', $limit);
        }

        $query = PopularMarket::query();
        $popularMarkets = $query->orderBy('id', 'DESC')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.elements.popularmarkets.admin_index', compact('popularMarkets'));
        }

        return view('admin.popularmarkets.index', compact('popularMarkets'));
    }

    public function add(Request $request, $id = null)
    {
        $id = base64_decode($id);
        $listTitle = $id ? 'Edit' : 'Add';
        
        $popularMarket = $id ? PopularMarket::find($id) : new PopularMarket();

        if ($request->isMethod('post')) {
            $data = $request->input('PopularMarket');
            $popularMarket->fill($data);
            
            if ($popularMarket->save()) {
                session()->flash('success', 'Record data ' . ($id ? 'updated' : 'saved') . ' successfully');
                return redirect()->route('admin.popular_markets.index');
            }
        }

        return view('admin.popularmarkets.add', compact('popularMarket', 'listTitle'));
    }

    public function status(Request $request, $id = null, $status = 0)
    {
        $id = base64_decode($id);
        $popularMarket = PopularMarket::find($id);

        if (!empty($popularMarket)) {
            $popularMarket->status = $status;
            $popularMarket->save();
            session()->flash('success', "Your request processed successfully.");
        } else {
            session()->flash('error', "Sorry, something went wrong.");
        }

        return back();
    }

    public function delete(Request $request, $id = null)
    {
        $id = base64_decode($id);
        $popularMarket = PopularMarket::find($id);

        if (!empty($popularMarket)) {
            $popularMarket->delete();
            session()->flash('success', "Your request processed successfully.");
        } else {
            session()->flash('error', "Sorry, selected record not found");
        }

        return back();
    }
}
