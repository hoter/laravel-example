<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CarModel;
use Maatwebsite\Excel\Facades\Excel;

class CarModelController extends Controller
{

	public function index()
	{
		$makes = CarModel::select( 'make' )->distinct()->get();

		return view( 'carModel.index', compact( 'makes') );
	}

	public function getCars( Request $request )
	{

		$cars = CarModel::where( 'make', $request->make_id )->get();
		return view( 'carModel.cars_list', compact( 'cars' ) );

	}

	public function editCarDetails( Request $request )
	{
		$data = CarModel::where( 'model_cap_id', $request->model_cap_id )->first();

		if ( !is_null( $data ) ) {
			$data->description = $request->description ?? null;
			$data->ppc_text = $request->ppc_text ?? null;
			$data->is_active = $request->is_active ?? null;
			$data->save();

			return \Response::json( [
				'id'          =>$request->model_cap_id,
				'description' => $request->description,
				'ppc_text'    => $request->ppc_text,
				'is_active'   => $request->is_active,
			] );
		}

	}

}
