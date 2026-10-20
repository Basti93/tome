<?php

namespace Tests\Feature;

use App\Support\ProfileImage;
use App\User;
use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\UserFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_index_returns_all_users_as_plain_json_array(): void
    {
        $trainer = $this->makeUser('trainer');
        $other = UserFactory::new()->inactive()->create();

        $response = $this->actingAsJwt($trainer)->getJson('/api/v1/user')->assertOk();

        $this->assertEqualsCanonicalizing([$trainer->id, $other->id], collect($response->json())->pluck('id')->all());
        $response->assertJsonStructure([[
            'id', 'firstName', 'familyName', 'email', 'active', 'registered', 'birthdate', 'profileImageName',
            'roleNames', 'groupIds', 'trainerBranchIds', 'name', 'failedLoginAttempts', 'lockedUntil',
            'emailVerifiedAt', 'absenceStart', 'absenceEnd', 'absenceReason',
        ]]);
        $response->assertJsonMissingPath('0.password');
        $response->assertJsonMissingPath('0.emailVerificationToken');
    }

    public function test_index_filters_by_group_branch_and_search_text(): void
    {
        $trainer = $this->makeUser('trainer');
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $inGroup = UserFactory::new()->inGroups([$group])->create(['firstName' => 'Gerda', 'familyName' => 'Gruppe']);
        UserFactory::new()->create(['firstName' => 'Nina', 'familyName' => 'Nirgends']);

        $byGroup = collect($this->actingAsJwt($trainer)->getJson("/api/v1/user?groupIds={$group->id}")->json())->pluck('id')->all();
        $byBranch = collect($this->actingAsJwt($trainer)->getJson("/api/v1/user?branchId={$branch->id}")->json())->pluck('id')->all();
        $bySearch = collect($this->actingAsJwt($trainer)->getJson('/api/v1/user?searchText=Gerd')->json())->pluck('id')->all();

        $this->assertSame([$inGroup->id], $byGroup);
        $this->assertSame([$inGroup->id], $byBranch);
        $this->assertSame([$inGroup->id], $bySearch);
    }

    public function test_index_paginates_when_per_page_is_given(): void
    {
        $trainer = $this->makeUser('trainer');
        UserFactory::new()->count(3)->create();

        $response = $this->actingAsJwt($trainer)->getJson('/api/v1/user?per_page=2')->assertOk();

        $response->assertJsonCount(2, 'data')->assertJsonPath('total', 4)->assertJsonPath('perPage', 2)->assertJsonPath('lastPage', 2);
    }

    public function test_sort_orders_and_paginates(): void
    {
        $trainer = $this->makeUser('trainer', ['familyName' => 'Mmm']);
        UserFactory::new()->create(['familyName' => 'Aaa']);
        UserFactory::new()->create(['familyName' => 'Zzz']);

        $names = collect($this->actingAsJwt($trainer)->getJson('/api/v1/user/sort?sortBy=familyName&direction=asc')->assertOk()->json('data'))->pluck('familyName')->all();

        $this->assertSame(['Aaa', 'Mmm', 'Zzz'], $names);
    }

    public function test_trainer_endpoint_lists_users_with_trainer_role_filtered_by_branch(): void
    {
        $member = $this->makeUser('member');
        $branch = BranchFactory::new()->create();
        $other = BranchFactory::new()->create();
        $mine = UserFactory::new()->role('trainer')->trainerOfBranches([$branch])->create();
        UserFactory::new()->role('trainer')->trainerOfBranches([$other])->create();

        $all = $this->actingAsJwt($this->makeUser('trainer'))->getJson('/api/v1/user/trainer')->assertOk()->json();
        $filtered = $this->actingAsJwt($this->makeUser('trainer'))->getJson("/api/v1/user/trainer?branchIds={$branch->id}")->json();

        $this->assertCount(3, $all);
        $this->assertSame([$mine->id], collect($filtered)->pluck('id')->all());
        $this->assertNotNull($member->id);
    }

    public function test_birthdays_are_returned_for_the_requested_month_day_range(): void
    {
        $trainer = $this->makeUser('trainer');
        UserFactory::new()->create(['firstName' => 'Bea', 'familyName' => 'Birth', 'birthdate' => '1990-06-15']);
        UserFactory::new()->create(['firstName' => 'Out', 'familyName' => 'Range', 'birthdate' => '1990-09-15']);
        UserFactory::new()->inactive()->create(['birthdate' => '1990-06-16']);

        $this->actingAsJwt($trainer)->getJson('/api/v1/user/birthdays?start=2099-06-01&end=2099-06-30')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.firstName', 'Bea')
            ->assertJsonPath('0.familyName', 'Birth')
            ->assertJsonPath('0.birthdate', '1990-06-15');

        // only the fields the calendar needs (formerly the appended roleNames/groupIds/... leaked as well)
        $keys = array_keys($this->actingAsJwt($trainer)->getJson('/api/v1/user/birthdays?start=2099-06-01&end=2099-06-30')->json('0'));
        sort($keys);
        $this->assertSame(['birthdate', 'familyName', 'firstName'], $keys);
    }

    public function test_all_absence_lists_active_users_with_an_absence_ordered_by_end(): void
    {
        $trainer = $this->makeUser('trainer');
        $later = UserFactory::new()->create(['absenceStart' => '2099-01-01', 'absenceEnd' => '2099-03-01', 'absenceReason' => 'b']);
        $sooner = UserFactory::new()->create(['absenceStart' => '2099-01-01', 'absenceEnd' => '2099-02-01', 'absenceReason' => 'a']);
        UserFactory::new()->inactive()->create(['absenceStart' => '2099-01-01', 'absenceEnd' => '2099-02-01']);
        UserFactory::new()->create();

        $response = $this->actingAsJwt($trainer)->getJson('/api/v1/user/allAbsence')->assertOk();

        $this->assertSame([$sooner->id, $later->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_remove_absence_clears_the_absence_fields(): void
    {
        $trainer = $this->makeUser('trainer');
        $user = UserFactory::new()->create(['absenceStart' => '2099-01-01', 'absenceEnd' => '2099-02-01', 'absenceReason' => 'x']);

        $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$user->id}/removeAbsence")->assertOk()->assertExactJson(['status' => 'ok']);

        $user->refresh();
        $this->assertNull($user->absenceStart);
        $this->assertNull($user->absenceEnd);
        $this->assertNull($user->absenceReason);
    }

    public function test_trainer_updates_a_user_including_groups_and_trainer_branches(): void
    {
        $trainer = $this->makeUser('trainer');
        $user = UserFactory::new()->create();
        // an account without login (no password yet): the trainer may set its e-mail address
        DB::table('users')->where('id', $user->id)->update(['password' => null]);
        $group = GroupFactory::new()->create();
        $branch = BranchFactory::new()->create();

        $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$user->id}", [
            'firstName' => 'Neu', 'familyName' => 'Name', 'email' => 'neu@example.com',
            'active' => 'false', 'groupIds' => [$group->id], 'trainerBranchIds' => [$branch->id],
            'profileImageName' => 'profile_images/'.str_repeat('n', 40).'.jpg',
        ])->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $user->refresh();
        $this->assertSame('Neu', $user->firstName);
        $this->assertSame('neu@example.com', $user->email);
        $this->assertSame(0, (int) $user->active);
        $this->assertSame('profile_images/'.str_repeat('n', 40).'.jpg', $user->profile_image_name);
        $this->assertSame([$group->id], $user->groups()->pluck('groups.id')->all());
        $this->assertSame([$branch->id], $user->trainerBranches()->pluck('branches.id')->all());
    }

    private function imageName(string $char = 'a', string $ext = 'jpg'): string
    {
        return 'profile_images/'.str_repeat($char, 40).'.'.$ext;
    }

    public function test_update_deletes_the_replaced_profile_image(): void
    {
        Storage::fake('public');
        $old = $this->imageName('o');
        Storage::disk('public')->put($old, 'x');
        $trainer = $this->makeUser('trainer');
        $user = UserFactory::new()->create(['profile_image_name' => $old]);

        $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$user->id}", [
            'firstName' => 'A', 'familyName' => 'B', 'profileImageName' => null,
        ])->assertStatus(201);

        Storage::disk('public')->assertMissing($old);
    }

    public function test_update_me_deletes_the_replaced_profile_image(): void
    {
        Storage::fake('public');
        $old = $this->imageName('o', 'png');
        $new = $this->imageName('n');
        Storage::disk('public')->put($old, 'x');
        Storage::disk('public')->put($new, 'y');
        $member = $this->makeUser('member', ['profile_image_name' => $old]);

        $this->actingAsJwt($member)->putJson('/api/v1/user/me', [
            'firstName' => 'A', 'familyName' => 'B', 'profileImageName' => $new,
        ])->assertStatus(201);

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
        $this->assertSame($new, $member->fresh()->profile_image_name);
    }

    public function test_profile_image_names_must_match_the_upload_naming_scheme(): void
    {
        $trainer = $this->makeUser('trainer');
        $user = UserFactory::new()->create();

        foreach (['../../.env', 'profile_images/../../.env', 'profile_images/short.jpg', 'profile_images/'.str_repeat('a', 40).'.php',
            'other/'.str_repeat('a', 40).'.jpg', '/etc/passwd', 'profile_images/'.str_repeat('a', 40).'.jpg/../x', 'profile_images/'.str_repeat('a', 65).'.jpg'] as $bad) {
            $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$user->id}", ['firstName' => 'A', 'familyName' => 'B', 'profileImageName' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors(['profileImageName']);
            $this->actingAsJwt($trainer)->putJson('/api/v1/user/me', ['firstName' => 'A', 'familyName' => 'B', 'profileImageName' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors(['profileImageName']);
        }

        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$user->id}", ['firstName' => 'A', 'familyName' => 'B', 'profileImageName' => $this->imageName('a', $ext)])
                ->assertStatus(201);
        }
    }

    public function test_only_files_matching_the_naming_scheme_are_ever_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('keep.txt', 'x');
        Storage::disk('public')->put('profile_images/legacy.jpg', 'x');
        $trainer = $this->makeUser('trainer');

        // legacy rows with an unexpected name (path traversal, short name) are not removed from disk
        foreach (['../keep.txt', 'profile_images/legacy.jpg'] as $odd) {
            $user = UserFactory::new()->create(['profile_image_name' => $odd]);
            $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$user->id}", ['firstName' => 'A', 'familyName' => 'B', 'profileImageName' => null])
                ->assertStatus(201);
        }

        Storage::disk('public')->assertExists('keep.txt');
        Storage::disk('public')->assertExists('profile_images/legacy.jpg');
    }

    public function test_update_validates_names(): void
    {
        $trainer = $this->makeUser('trainer');
        $user = UserFactory::new()->create();

        $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$user->id}", [])
            ->assertStatus(422)->assertJsonValidationErrors(['firstName', 'familyName']);
        $this->actingAsJwt($trainer)->putJson('/api/v1/user/999999', ['firstName' => 'A', 'familyName' => 'B'])->assertNotFound();
    }

    public function test_update_me_changes_own_profile_but_not_groups_for_members(): void
    {
        $group = GroupFactory::new()->create();
        $member = $this->makeUser('member', ['email' => 'me@example.com']);

        $this->actingAsJwt($member)->putJson('/api/v1/user/me', [
            'firstName' => 'Mia', 'familyName' => 'Meyer', 'email' => 'mia@example.com',
            'birthdate' => '1999-04-03T00:00:00+0000', 'groupIds' => [$group->id], 'profileImageName' => null,
        ])->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $member->refresh();
        $this->assertSame('Mia', $member->firstName);
        $this->assertSame('mia@example.com', $member->email);
        $this->assertSame('1999-04-03', $member->birthdate);
        $this->assertCount(0, $member->groups, 'members cannot assign themselves to groups');
    }

    public function test_update_me_allows_trainers_to_set_their_groups(): void
    {
        $group = GroupFactory::new()->create();
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->putJson('/api/v1/user/me', [
            'firstName' => 'T', 'familyName' => 'R', 'groupIds' => [$group->id], 'trainerBranchIds' => [],
        ])->assertStatus(201);

        $this->assertSame([$group->id], $trainer->groups()->pluck('groups.id')->all());
    }

    public function test_create_unregistered_creates_a_user_without_credentials(): void
    {
        $trainer = $this->makeUser('trainer');
        $group = GroupFactory::new()->create();

        $this->actingAsJwt($trainer)->postJson('/api/v1/user/unregistered', [
            'firstName' => 'Uli', 'familyName' => 'Unreg', 'birthdate' => '2010-02-02', 'groupIds' => [$group->id], 'active' => 'true',
        ])->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $user = User::where('firstName', 'Uli')->firstOrFail();
        $this->assertSame(0, (int) $user->registered);
        $this->assertNull($user->email);
        $this->assertNull($user->password);
        $this->assertSame(1, (int) $user->active);
        $this->assertSame([$group->id], $user->groups()->pluck('groups.id')->all());
    }

    public function test_create_unregistered_validation(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->postJson('/api/v1/user/unregistered', [])
            ->assertStatus(422)->assertJsonValidationErrors(['firstName', 'familyName']);
    }

    public function test_destroy_requires_delete_user_permission_which_only_admins_have(): void
    {
        $user = UserFactory::new()->create();

        $this->actingAsJwt($this->makeUser('trainer'))->deleteJson("/api/v1/user/{$user->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAsJwt($this->makeUser('admin'))->deleteJson("/api/v1/user/{$user->id}")
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->actingAsJwt($this->makeUser('admin'))->deleteJson('/api/v1/user/999999')->assertNotFound();
    }

    public function test_profile_image_upload_stores_a_reencoded_jpeg_on_the_public_disk(): void
    {
        Storage::fake('public');
        Storage::fake('public_app');
        $member = $this->makeUser('member');

        $response = $this->actingAsJwt($member)->post('/api/v1/user/me/uploadprofileimage', [
            'profile_image' => UploadedFile::fake()->image('me.png', 800, 800),
        ], ['Accept' => 'application/json'])->assertStatus(201)->assertJsonPath('status', 'ok');

        $path = $response->json('imageUrl');
        $this->assertMatchesRegularExpression('#^profile_images/[A-Za-z0-9]{40}\.jpg$#', $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public_app')->assertDirectoryEmpty('/');
        [$width, $height, $type] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(400, $height, 'images are resized to a height of 400px');
        $this->assertSame(400, $width);
        $this->assertSame(IMAGETYPE_JPEG, $type, 'always re-encoded, even if a png was uploaded');
        // the stored path satisfies the profileImageName rule used when saving the profile
        $this->assertSame(1, preg_match(ProfileImage::PATTERN, $path));
    }

    public function test_profile_image_upload_keeps_the_aspect_ratio(): void
    {
        Storage::fake('public');
        $member = $this->makeUser('member');

        $response = $this->actingAsJwt($member)->post('/api/v1/user/me/uploadprofileimage', [
            'profile_image' => UploadedFile::fake()->image('wide.jpg', 1200, 600),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        [$width, $height] = getimagesize(Storage::disk('public')->path($response->json('imageUrl')));
        $this->assertSame(400, $height);
        $this->assertSame(800, $width, '1200x600 is scaled to 800x400, not squeezed');
    }

    public function test_profile_image_upload_uses_a_random_name_every_time(): void
    {
        Storage::fake('public');
        $member = $this->makeUser('member');
        $names = [];
        for ($i = 0; $i < 2; $i++) {
            $names[] = $this->actingAsJwt($member)->post('/api/v1/user/me/uploadprofileimage', [
                'profile_image' => UploadedFile::fake()->image('same.jpg', 200, 200),
            ], ['Accept' => 'application/json'])->assertStatus(201)->json('imageUrl');
        }

        $this->assertNotSame($names[0], $names[1]);
        $this->assertStringNotContainsString('same', $names[0]);
    }

    public function test_profile_image_upload_without_file_is_400(): void
    {
        $member = $this->makeUser('member');

        $this->actingAsJwt($member)->postJson('/api/v1/user/me/uploadprofileimage')
            ->assertStatus(400)->assertExactJson(['status' => 'error', 'message' => 'No file provided']);
    }

    public function test_profile_image_upload_rejects_non_images_and_active_content(): void
    {
        Storage::fake('public');
        $member = $this->makeUser('member');

        $rejected = [
            UploadedFile::fake()->createWithContent('x.txt', 'not an image'),
            UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
            // a PHP file with a harmless-looking image name and content type
            UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>'),
            UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->createWithContent('anim.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')),
        ];
        foreach ($rejected as $file) {
            $this->actingAsJwt($member)->post('/api/v1/user/me/uploadprofileimage', ['profile_image' => $file], ['Accept' => 'application/json'])
                ->assertStatus(422)->assertJsonValidationErrors(['profileImage']);
        }

        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_profile_image_upload_enforces_size_and_dimension_limits(): void
    {
        Storage::fake('public');
        $member = $this->makeUser('member');

        $tooBig = UploadedFile::fake()->image('big.jpg', 100, 100)->size(5121);
        $tooSmall = UploadedFile::fake()->image('tiny.jpg', 10, 10);
        $tooLarge = UploadedFile::fake()->image('huge.jpg', 8001, 100);
        foreach ([$tooBig, $tooSmall, $tooLarge] as $file) {
            $this->actingAsJwt($member)->post('/api/v1/user/me/uploadprofileimage', ['profile_image' => $file], ['Accept' => 'application/json'])
                ->assertStatus(422)->assertJsonValidationErrors(['profileImage']);
        }

        $this->actingAsJwt($member)->post('/api/v1/user/me/uploadprofileimage', [
            'profile_image' => UploadedFile::fake()->image('ok.webp', 300, 300),
        ], ['Accept' => 'application/json'])->assertStatus(201);
    }

    public function test_profile_image_upload_requires_login(): void
    {
        $this->postJson('/api/v1/user/me/uploadprofileimage')->assertStatus(401);
    }

    public function test_profile_image_is_removed_again_when_processing_fails(): void
    {
        Storage::fake('public');
        $member = $this->makeUser('member');

        // passes the mime check (valid PNG signature + IHDR) but cannot be decoded
        $broken = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NNCCCCC', 100, 100, 8, 2, 0, 0, 0).'garbage';
        $this->actingAsJwt($member)->post('/api/v1/user/me/uploadprofileimage', [
            'profile_image' => UploadedFile::fake()->createWithContent('broken.png', $broken),
        ], ['Accept' => 'application/json'])->assertStatus(500)
            ->assertExactJson(['status' => 'error', 'message' => 'Failed to upload image']);

        Storage::disk('public')->assertDirectoryEmpty('profile_images');
    }
}
