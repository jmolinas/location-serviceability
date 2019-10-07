<?php

namespace GP\LocationServiceability\Http\Controllers;

use GP\LocationServiceability\Http\Requests\Service\ServiceOwnerRequest;
use GP\LocationServiceability\Http\Requests\Service\ServiceSearchRequest;
use GP\LocationServiceability\Http\Requests\Service\ServiceUpdateRequest;
use GP\LocationServiceability\Http\Requests\ServiceRequest;
use GP\LocationServiceability\Http\Resources\Service as ServiceResource;
use GP\LocationServiceability\Http\Resources\ServiceCollection;
use GP\LocationServiceability\Models\Service;
use GP\LocationServiceability\Models\User;
use GP\LocationServiceability\Services\CustomGooglePlaces;
use GP\LocationServiceability\Services\Service\ServiceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use GP\LocationServiceability\Services\ServicePaginator;

class ServiceController extends Controller
{
    /**
     * @param \GP\LocationServiceability\Http\Requests\Service\ServiceSearchRequest $request
     * @return \GP\LocationServiceability\Http\Resources\ServiceCollection
     */
    public function index(ServiceSearchRequest $request)
    {
        return new ServiceCollection(
            app(ServicePaginator::class)->get(
                $request->get('s'),
                $request->get('categories', []),
                $request->only(['lat', 'lng', 'distance']),
                $request->get('state_code')
            )
        );
    }

    /**
     * @param \GP\Models\User $user
     * @param \Illuminate\Http\Request $request
     * @return \GP\LocationServiceability\Http\Resources\ServiceCollection
     */
    public function fetchUser(User $user, Request $request)
    {
        $services = app(ServicePaginator::class)->getUser($user);

        return new ServiceCollection($services);
    }

    /**
     * @param \GP\Models\Service $service
     * @return \GP\LocationServiceability\Http\Resources\Service
     */
    public function show(Service $service)
    {
        $service->load([
            'categories',
            'reviews' => function ($query) {
                $query->orderBy('created_at', 'DESC');
            },
            'reviews.author'
        ]);

        $service->setAttribute('avg_score', $service->avg_score);

        return new ServiceResource($service);
    }

    /**
     * @param \GP\LocationServiceability\Http\Requests\ServiceRequest $request
     * @return \GP\LocationServiceability\Http\Resources\Service
     */
    public function store(ServiceRequest $request)
    {
        $data = $request->validated();

        /** @var \GP\Models\Service $service */
        $service = Service::make([
            'title'       => $data['title'],
            'price'       => $data['price'],
            'description' => $data['description'],
            'user_id'     => Auth::user()->id
        ]);

        // set and store photo
        if ($data['photo'] !== null) {
            $service->photo = Service::DIR_PHOTO . '/' . $data['photo']->hashName();

            Storage::disk('img')->put($service->photo, $data['photo']->get());
        }

        $location = CustomGooglePlaces::make($data['location'])->makeLocation();

        $location->save();
        $service->location()->associate($location);
        $service->save();
        $service->categories()->attach($data['categories']);

        $resource = new ServiceResource($service);
        $resource->additional([
            'message' => 'Service Added Successfully!'
        ]);

        return $resource;
    }

    /**
     * @param \GP\Models\Service $service
     * @param \GP\LocationServiceability\Http\Requests\Service\ServiceOwnerRequest $request
     * @return \GP\LocationServiceability\Http\Resources\Service
     */
    public function showUpdate(Service $service, ServiceOwnerRequest $request)
    {
        $service->load('categories');

        return new ServiceResource($service);
    }

    /**
     * @param \GP\Models\Service $service
     * @param \GP\LocationServiceability\Http\Requests\Service\ServiceUpdateRequest $request
     * @return \GP\LocationServiceability\Http\Resources\Service
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function update(Service $service, ServiceUpdateRequest $request)
    {
        app(ServiceManager::class)->processUpdate(
            $service,
            $request->all(),
            $request->file('photo')
        );

        $service->load('categories');

        return new ServiceResource($service);
    }
}
