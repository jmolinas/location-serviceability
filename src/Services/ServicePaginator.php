<?php


namespace GP\LocationServiceability\Services;


use GP\LocationServiceability\Models\Service;
use GP\LocationServiceability\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ServicePaginator
{
    const PER_PAGE = 10;

    /**
     * @param $search
     * @param array $categories
     * @param array $location
     * @param $stateCode
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function get($search, array $categories, array $location, $stateCode)
    {
        $baseQuery = Service::query();

        if ($search) {
            $likeSearch = "%{$search}%";

            $baseQuery->where(function($query) use ($likeSearch) {
                $query->where('title', 'LIKE', $likeSearch)
                    ->orWhere('description', 'LIKE', $likeSearch);
            });
        }

        if (count($categories)) {
            $baseQuery->whereHas('categories', function ($query) use ($categories) {
                $query->whereIn('name', $categories);
            });
        }

        if (count($location)) {
            $baseQuery->whereHas('location', function ($query) use ($location) {
                $query->withinGeolocation(
                    $location['lat'],
                    $location['lng'],
                    $location['distance']
                );
            });
        }

        if ($stateCode) {
            $baseQuery->whereHas('location', function ($query) use ($stateCode) {
                $query->whereHas('state', function ($locationQuery) use ($stateCode) {
                    $locationQuery->where('code', $stateCode);
                });
            });
        }

        return $this->paginate($baseQuery);
    }

    /**
     * @param \GP\LocationServiceability\Models\User $user
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUser(User $user)
    {
        return $this->paginate(
            Service::where('user_id', $user->id)
        );
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $baseQuery
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function paginate(Builder $baseQuery)
    {
        $baseQuery->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        $results = $baseQuery->paginate(self::PER_PAGE);

        $results->getCollection()->each(function ($service) {
            $service->setAttribute('avg_score', $service->avg_score);
        });

        return $results;
    }
}
