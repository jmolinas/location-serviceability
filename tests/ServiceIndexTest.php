<?php

use GP\LocationServiceability\Models\Category;
use GP\LocationServiceability\Models\Location;
use GP\LocationServiceability\Models\Service;
use GP\LocationServiceability\Models\ServiceCategory;
use GP\LocationServiceability\Models\State;
use GP\LocationServiceability\Models\User;
use Tests\TestCase;

class ServiceIndexTest extends TestCase
{
    const URI = '/api/services';

    /** @test */
    public function second_page()
    {
        $user = factory(User::class)->create();

        factory(Service::class, 30)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=2'
        );

        $response->assertSuccessful()
            ->assertJson([
                    'data' => [
                        ['id' => 20],
                        ['id' => 19],
                        ['id' => 18],
                        ['id' => 17],
                        ['id' => 16],
                        ['id' => 15],
                        ['id' => 14],
                        ['id' => 13],
                        ['id' => 12],
                        ['id' => 11],
                    ],
                ]
            );

        $this->assertCount(10, $response->json('data'));
        $this->assertCount(30, Service::all());
    }

    /** @test */
    public function filter_title()
    {
        $user = factory(User::class)->create();

        factory(Service::class)->create([
            'title' => 'some title',
        ]);
        factory(Service::class, 15)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&s=title'
        );

        $response->assertSuccessful()
            ->assertJson([
                    'data' => [
                        ['title' => 'some title'],
                    ],
                ]
            );

        $this->assertCount(1, $response->json('data'));
        $this->assertCount(16, Service::all());
    }

    /** @test */
    public function filter_description()
    {
        $user = factory(User::class)->create();

        factory(Service::class)->create([
            'description' => 'some description',
        ]);
        factory(Service::class, 15)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&s=description'
        );

        $response->assertSuccessful()
            ->assertJson([
                    'data' => [
                        ['description' => 'some description'],
                    ],
                ]
            );

        $this->assertCount(1, $response->json('data'));
        $this->assertCount(16, Service::all());
    }

    /** @test */
    public function filter_title_description_no_results()
    {
        $user = factory(User::class)->create();

        factory(Service::class, 15)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&s=SOME_STRING_THAT_CANNOT_BE_SEARCHED'
        );

        $response->assertSuccessful()
            ->assertJson([
                    'data' => [],
                ]
            );

        $this->assertCount(0, $response->json('data'));
        $this->assertCount(15, Service::all());
    }

    /** @test */
    public function filter_search_and_category()
    {
        $user = factory(User::class)->create();

        $services = [
            factory(Service::class)->create([
                'title' => 'some title',
            ]),
            factory(Service::class)->create([
                'title' => 'title yeah',
            ]),
        ];

        $categories = [
            factory(Category::class)->create([
                'name' => 'cat1',
            ]),
        ];

        factory(ServiceCategory::class)->create([
            'service_id'  => $services[0]->id,
            'category_id' => $categories[0]->id,
        ]);

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&s=title'
            . '&categories[]=cat1'
        );

        $response->assertSuccessful()
            ->assertJson([
                    'data' => [
                        ['title' => 'some title'],
                    ],
                ]
            );

        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function filter_location()
    {
        $this->markTestSkipped('Only test on mysql database');

        $user = factory(User::class)->create();

        // Brooklyn Hospital Center - Downtown, Dekalb Avenue, Brooklyn, NY, USA
        $state = factory(State::class)->create([
            'name' => 'New York',
            'code' => 'NY',
        ]);
        $location = factory(Location::class)->create([
            'city'           => 'New York',
            'zip'            => 11201,
            'street_address' => 'Brooklyn Hospital Center - Downtown, Dekalb Avenue',
            'state_id'       => $state->id,
            'latitude'       => '40.6904961',
            'longitude'      => '-73.9784143',
        ]);

        factory(Service::class)->create([
            'title'       => 'some service',
            'location_id' => $location->id,
        ]);
        factory(Service::class, 15)->create();

        // WIC Center, Dekalb Avenue, Brooklyn, NY, USA
        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&lat=40.6905696'
            . '&lng=-73.97772499999996'
            . '&distance=2'
        );

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    ['title' => 'some service']
                ]
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function filter_location_out_of_range()
    {
        $this->markTestSkipped('Only test on mysql database');

        $user = factory(User::class)->create();

        // Brooklyn Hospital Center - Downtown, Dekalb Avenue, Brooklyn, NY, USA
        $state = factory(State::class)->create([
            'name' => 'New York',
            'code' => 'NY',
        ]);
        $location = factory(Location::class)->create([
            'city'           => 'New York',
            'zip'            => 11201,
            'street_address' => 'Brooklyn Hospital Center - Downtown, Dekalb Avenue',
            'state_id'       => $state->id,
            'latitude'       => '40.6904961',
            'longitude'      => '-73.9784143',
        ]);

        factory(Service::class)->create([
            'title'       => 'some service',
            'location_id' => $location->id,
        ]);
        factory(Service::class, 15)->create();

        //  4.50616 km away, Hotel on Rivington, Rivington Street, New York, NY, USA
        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&lat=40.719762'
            . '&lng=-73.98809299999999'
            . '&distance=2'
        );

        $response->assertSuccessful()
            ->assertJson([
                'data' => []
            ]);

        $this->assertCount(0, $response->json('data'));
        $this->assertCount(16, Service::all());
    }

    /** @test */
    public function filter_state_code()
    {
        $user = factory(User::class)->create();

        $state = factory(State::class)->create([
            'name' => 'New York',
            'code' => 'NY',
        ]);
        $location = factory(Location::class)->create([
            'city'           => 'New York',
            'zip'            => 11201,
            'street_address' => 'Brooklyn Hospital Center - Downtown, Dekalb Avenue',
            'state_id'       => $state->id,
            'latitude'       => '40.6904961',
            'longitude'      => '-73.9784143',
        ]);

        factory(Service::class)->create([
            'title'       => 'some service',
            'location_id' => $location->id,
        ]);
        factory(Service::class, 15)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&state_code=NY'
        );

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    ['title' => 'some service']
                ]
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertCount(16, Service::all());
    }

    /** @test */
    public function validate_lat_require_missing()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&lat=40.6781784'
        );

        $response->assertStatus(422);

        $errors = $response->json('errors');

        $this->assertContains('required', $errors['lng'][0]);
        $this->assertContains('required', $errors['distance'][0]);
    }

    /** @test */
    public function validate_lng_require_missing()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&lng=-73.9441579'
        );

        $response->assertStatus(422);

        $errors = $response->json('errors');

        $this->assertContains('required', $errors['lat'][0]);
        $this->assertContains('required', $errors['distance'][0]);
    }

    /** @test */
    public function validate_distance_require_missing()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&distance=2'
        );

        $response->assertStatus(422);

        $errors = $response->json('errors');

        $this->assertContains('required', $errors['lat'][0]);
        $this->assertContains('required', $errors['lng'][0]);
    }

    /** @test */
    public function validate_state_code_required()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&state_code='
        );

        $response->assertStatus(422);

        $errors = $response->json('errors');

        $this->assertContains('required', $errors['state_code'][0]);
    }

    /** @test */
    public function validate_state_code_exists()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->getJson(
            self::URI
            . '?page=1'
            . '&state_code=NON_EXISTENT_STATE_CODE'
        );

        $response->assertStatus(422);

        $errors = $response->json('errors');

        $this->assertContains('invalid', $errors['state_code'][0]);
    }
}
