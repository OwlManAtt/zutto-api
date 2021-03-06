<?php

use Laravel\Lumen\Testing\DatabaseMigrations;

class PetTest extends TestCase
{
    use DatabaseMigrations;

    public function testCreate()
    {
        $resp = $this->actingAs($this->user)->json('PUT', '/pet', [
            'pet_type_id' => 1,
            'name' => 'Steve',
        ]);

        $resp->assertResponseOk();
        $resp->seeJsonStructure(['id', 'skills', 'type', 'skin']);
    } // end testCreate

    public function testPetList()
    {
        $resp = $this->actingAs($this->user)->json('GET', '/pet');
        
        $resp->assertResponseOk();
        $resp->seeJsonStructure(['*' => ['name', 'type', 'skin', 'skills']]);
    } // end testPetList

    public function testGetPet()
    {
        $user = factory('App\Models\User')->create();
        $pet = factory('App\Models\Pet')->create(['user_id' => $user->id]);

        $resp = $this->actingAs($user)->json('GET', '/pet/' . $pet->id);
        
        $resp->assertResponseOk();
        $resp->seeJsonStructure(['name', 'type', 'skin', 'skills']);
    } // end testGetPet
} // end RollTableTest
