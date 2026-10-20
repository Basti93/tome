<?php

namespace Tests\Feature\Training;

use App\Group;
use App\Training;
use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Tests\TestCase;

class TrainingNoteTest extends TestCase
{
    private function group(): Group
    {
        return GroupFactory::new()->create();
    }

    public function test_complete_stores_the_note_trimmed_and_an_empty_note_clears_it(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->inPast()->withTrainers([$trainer])->create();

        $this->actingAsUser($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/complete", ['attendeeIds' => [], 'note' => "  Aufwärmen kürzen \n"])
            ->assertOk()->assertJsonPath('data.note', 'Aufwärmen kürzen');
        $this->assertSame('Aufwärmen kürzen', $t->fresh()->note);

        // without the key the note stays, an empty one clears it
        $this->actingAsUser($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/complete", ['attendeeIds' => []])->assertOk();
        $this->assertSame('Aufwärmen kürzen', $t->fresh()->note);
        $this->actingAsUser($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/complete", ['attendeeIds' => [], 'note' => ''])->assertOk();
        $this->assertNull($t->fresh()->note);
    }

    public function test_note_is_limited_to_1000_characters(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->inPast()->withTrainers([$trainer])->create();

        $this->actingAsUser($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/complete", ['attendeeIds' => [], 'note' => str_repeat('a', 1001)])
            ->assertStatus(422);
        $this->actingAsUser($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/note", ['note' => str_repeat('a', 1001)])->assertStatus(422);
        $this->actingAsUser($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/note", ['note' => str_repeat('a', 1000)])->assertOk();
    }

    public function test_assigned_trainer_and_admin_can_edit_the_note_afterwards_others_cannot(): void
    {
        $trainer = $this->makeUser('trainer');
        $other = $this->makeUser('trainer');
        $admin = $this->makeUser('admin');
        $t = TrainingFactory::new()->inPast()->withTrainers([$trainer])->create();
        $url = "/api/v1/trainingevaluation/{$t->id}/note";

        $this->actingAsUser($trainer)->postJson($url, ['note' => 'Eins'])->assertOk()->assertJsonPath('data.note', 'Eins');
        $this->actingAsUser($admin)->postJson($url, ['note' => 'Zwei'])->assertOk();
        $this->assertSame('Zwei', $t->fresh()->note);
        $this->actingAsUser($other)->postJson($url, ['note' => 'Drei'])->assertForbidden();
        $this->actingAsUser($this->makeUser('member'))->postJson($url, ['note' => 'Vier'])->assertForbidden();
        $this->assertSame('Zwei', $t->fresh()->note);
    }

    public function test_group_notes_list_the_last_ten_past_notes_of_the_groups_newest_first(): void
    {
        $trainer = $this->makeUser('trainer');
        $g = $this->group();
        $otherGroup = $this->group();
        for ($i = 1; $i <= 12; $i++) {
            TrainingFactory::new()->inPast($i)->forGroups([$g])->create(['note' => "Note $i"]);
        }
        TrainingFactory::new()->inPast(1)->forGroups([$g])->create(['note' => null]);
        TrainingFactory::new()->inPast(1)->forGroups([$otherGroup])->create(['note' => 'Andere Gruppe']);
        TrainingFactory::new()->forGroups([$g])->create(['note' => 'Zukunft']);

        $response = $this->actingAsUser($trainer)->getJson("/api/v1/trainingprepare/notes?groupIds={$g->id}")->assertOk();

        $notes = collect($response->json('data'))->pluck('note')->all();
        $this->assertCount(10, $notes);
        $this->assertSame('Note 1', $notes[0]);
        $this->assertSame('Note 10', $notes[9]);
        $response->assertJsonStructure(['data' => [['trainingId', 'start', 'groupIds', 'note']]]);
    }

    public function test_group_notes_without_groups_are_empty_and_need_a_trainer(): void
    {
        $this->actingAsUser($this->makeUser('trainer'))->getJson('/api/v1/trainingprepare/notes')->assertOk()->assertExactJson(['data' => []]);
        $this->actingAsUser($this->makeUser('member'))->getJson('/api/v1/trainingprepare/notes?groupIds=1')->assertForbidden();
    }

    public function test_group_notes_need_a_login(): void
    {
        $this->getJson('/api/v1/trainingprepare/notes?groupIds=1')->assertUnauthorized();
    }

    public function test_the_note_never_shows_up_in_public_or_plain_training_responses(): void
    {
        $trainer = $this->makeUser('trainer');
        $g = $this->group();
        $t = TrainingFactory::new()->forGroups([$g])->withTrainers([$trainer])->create(['note' => 'GEHEIM-NOTIZ']);

        foreach (['/api/v1/training/upcoming', "/api/v1/training/upcoming/{$t->id}", '/api/v1/training/simplecalendar'] as $url) {
            $this->assertStringNotContainsString('GEHEIM-NOTIZ', $this->getJson($url)->getContent(), $url);
        }
        $this->assertStringNotContainsString('GEHEIM-NOTIZ', $this->actingAsUser($trainer)->getJson("/api/v1/training/{$t->id}")->getContent());
        $this->assertStringNotContainsString('GEHEIM-NOTIZ', json_encode(Training::find($t->id)->toArray()));
        $this->assertStringNotContainsString('GEHEIM-NOTIZ', $this->actingAsUser($trainer)->getJson("/api/v1/trainingprepare/{$trainer->id}")->getContent());
    }
}
