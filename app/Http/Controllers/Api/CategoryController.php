<?php

namespace App\Http\Controllers\Api;

use App\Database\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;

use Illuminate\Support\Facades\Validator;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

use App\Database\Serializers\CustomSerializer;
use App\Database\Repositories\CategoryRepository;
use App\Database\Transformers\CategoryTransformer;

use StringHelper;
use Log;

class CategoryController extends ApiController
{
    protected $categoryRepository;

    public function __construct (
        ResponseFactory 		$response,
        Request 				$request,
        CategoryRepository      $categoryRepository
    ) {
        parent::__construct($response, $request);
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $model = $this->categoryRepository->findWhere([
            'isDeleted' => 0,
            'iActive'   => 1,
        ]);

        if ($model) {
            $fractalManager = new Manager();
            $fractalManager->setSerializer(new CustomSerializer());
            $model = new Collection($model, new CategoryTransformer());
            $model = $fractalManager->createData($model)->toArray();

            return $this->respond($model);
        } else {
            return $this->respond();
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
