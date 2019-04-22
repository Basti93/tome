<?php

use App\Branch;
use App\Content;
use App\Group;
use App\Training;
use App\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class OldDatabaseMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->migrateMembers();
        $this->migrateTrainers();
        $this->migrateContent();
        $this->migrateTrainings();
    }

    function migrateMembers()
    {
        $members = DB::connection("mysqlOld")->table('MEMBERS')->get();
        $roleMember = Role::findByName('member');

        foreach ($members as $member) {
            $user = new User();
            $user->firstName = $member->FIRST_NAME;
            $user->familyName = $member->FAMILY_NAME;
            $user->birthdate = $member->BIRTHDATE;
            $user->active = $member->ACTIVE;
            $user->registered = 0;
            $oldGroup = DB::connection("mysqlOld")->table('MEMBER_GROUPS')->where('ID', $member->GROUP_ID)->first();
            $group = Group::whereName($oldGroup->NAME)->first();
            $user->save();
            $user->groups()->attach($group);
            $user->assignRole($roleMember);
        }
    }

    function migrateContent()
    {
        $oldContent = DB::connection("mysqlOld")->table('TRAINING_CONTENTS')->get();
        foreach ($oldContent as $oc) {
            $content = new Content();
            $content->name = $oc->NAME;
            $content->order = $oc->OLYMPIC_ORDER;
            $content->branch_id = Branch::whereName('Gerätturnen männlich')->first()->id;

            if ($oc->NAME === 'Boden') {
                $content->svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 125"><title>Boden</title><path d="M50 0C22.4 0 0 22.4 0 50s22.4 50 50 50 50-22.4 50-50S77.6 0 50 0zm26.5 76.5h-53v-53h53v53z"/><path d="M27.7 27.7v44.5h44.5V27.7H27.7zM69 69H31V31h38v38z"/></svg>';
            } else if ($oc->NAME === 'Pauschenpferd') {
                $content->svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 125"><title>Pauschenpferd</title><path d="M50 0C22.4 0 0 22.4 0 50s22.4 50 50 50 50-22.4 50-50S77.6 0 50 0zm34.3 57.9c0 1.6-1.3 2.9-2.9 2.9h-63c-1.6 0-2.9-1.3-2.9-2.9V47.2c0-1.6 1.3-2.9 2.9-2.9h20v-5.9c0-.8.6-1.4 1.4-1.4.8 0 1.4.6 1.4 1.4v5.9h17.4v-5.9c0-.8.6-1.4 1.4-1.4.8 0 1.4.6 1.4 1.4v5.9h20c1.6 0 2.9 1.3 2.9 2.9v10.7z"/></svg>';
            } else if ($oc->NAME === 'Ringe') {
                $content->svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 125"><title>Ringe</title><path d="M50 0C22.4 0 0 22.4 0 50s22.4 50 50 50 50-22.4 50-50S77.6 0 50 0zM33.5 75.8c-5.6 0-10.2-4.6-10.2-10.2 0-5.2 3.9-9.5 8.9-10.1V24.2h2.6v31.1c5 .7 8.9 4.9 8.9 10.1 0 5.8-4.6 10.4-10.2 10.4zm33 0c-5.6 0-10.2-4.6-10.2-10.2 0-5.2 3.9-9.5 8.9-10.1V24.2h2.6v31.1c5 .7 8.9 4.9 8.9 10.1 0 5.8-4.5 10.4-10.2 10.4z"/><path d="M72.8 65.5c0 3.5-2.8 6.3-6.3 6.3s-6.3-2.8-6.3-6.3 2.8-6.3 6.3-6.3 6.3 2.9 6.3 6.3zM39.8 65.5c0 3.5-2.8 6.3-6.3 6.3s-6.3-2.8-6.3-6.3 2.8-6.3 6.3-6.3c3.4 0 6.3 2.9 6.3 6.3z"/>#</svg>';
            } else if ($oc->NAME === 'Sprung') {
                $content->svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 125"><title>Sprung</title><path d="M50 0C22.4 0 0 22.4 0 50s22.4 50 50 50 50-22.4 50-50S77.6 0 50 0zm33.5 52.9c-.8.4-1.6.7-2.4.7-1.7 0-3.4-.9-4.3-2.5-6-10.5-10.7-12.4-18.4-12.8v40.8c0 .8-.6 1.4-1.4 1.4H42.5c-.8 0-1.4-.6-1.4-1.4V38.2H22.3c-2.7 0-4.9-2.2-4.9-4.9s2.2-4.9 4.9-4.9H54c5.9 0 11.7.2 17.1 2.9 5.5 2.6 9.9 7.3 14.3 15 1.3 2.3.5 5.3-1.9 6.6z"/></svg>';
            } else if ($oc->NAME === 'Barren') {
                $content->svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 125"><title>Barren</title><path d="M50 0C22.4 0 0 22.4 0 50s22.4 50 50 50 50-22.4 50-50S77.6 0 50 0zm-6.3 75.7c0 1.7-1.4 3-3 3-1.7 0-3-1.4-3-3V24.3c0-1.7 1.4-3 3-3 1.7 0 3 1.4 3 3v51.4zm18.6 0c0 1.7-1.4 3-3 3-1.7 0-3-1.4-3-3V24.3c0-1.7 1.4-3 3-3 1.7 0 3 1.4 3 3v51.4z"/></svg>';
            } else if ($oc->NAME === 'Reck') {
                $content->svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 125"><title>Reck</title><path d="M50 0C22.4 0 0 22.4 0 50s22.4 50 50 50 50-22.4 50-50S77.6 0 50 0zm30.5 73.6h-6V32.4h-49v41.2h-6V26.4h61v47.2z"/></svg>';
            } else if ($oc->NAME === 'Krafttraining') {
                $content->svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><title>Krafttraining</title><path d="M511.539 276.606a71.613 71.613 0 0 0-3.122-20.686l-35.335-115.096c-3.753-12.223-16.649-19.143-28.909-15.514l-38.987 11.544c-8.478 2.509-14.263 9.722-15.391 17.947l36.822-9.593-53.965 30.189-42.491-21.811a60.329 60.329 0 0 1-19.811 26.38l56.377 28.91c.003.002.008.003.011.006 4.594 2.354 10.448 2.449 15.328-.269l.023-.011 58.32-33.678-36.47 34.494 19.204 64.89-28.521 72.853a19.597 19.597 0 0 0-1.211 9.457l10.258 86.381c1.276 10.748 11.021 18.428 21.775 17.152 10.749-1.276 18.429-11.026 17.151-21.775l-9.678-81.501 29.365-75.008 10.064-2.979.451 161.854c.03 10.806 8.799 19.546 19.6 19.546h.055c10.824-.03 19.576-8.83 19.546-19.655-.001-1.4-.296-110.43-.459-164.027z"/><circle cx="412.669" cy="85.729" r="34.018"/><path d="M304.291 290.551a17.942 17.942 0 0 0-14.456-17.483v-82.983a59.858 59.858 0 0 1-31.516 0v83.636l-94.498 3.496-17.652-47.107a18.352 18.352 0 0 0-31.559-4.971l-71.573 90.156c-6.302 7.938-4.975 19.482 2.962 25.784 7.938 6.301 19.484 4.976 25.784-2.963l51.399-64.744 19.844 52.629a30.58 30.58 0 0 0 28.746 19.79l114.911-.505c9.937-.061 17.948-8.179 17.874-18.119l-.266-36.616z"/><circle cx="354.332" cy="310.133" r="34.008"/><path d="M274.078 85.796c-25.598 0-46.349 20.751-46.349 46.349 0 25.598 20.751 46.349 46.349 46.349 25.598 0 46.349-20.751 46.349-46.349s-20.751-46.349-46.349-46.349zm0 71.76c-14.012 0-25.411-11.4-25.411-25.411 0-14.012 11.4-25.411 25.411-25.411s25.411 11.4 25.411 25.411c0 14.012-11.398 25.411-25.411 25.411z"/><path d="M274.078 118.155c-7.713 0-13.99 6.277-13.99 13.992 0 7.715 6.276 13.99 13.99 13.99 7.715 0 13.99-6.276 13.99-13.99.001-7.715-6.276-13.992-13.99-13.992zM365.257 363.818H14.275C6.391 363.818 0 370.208 0 378.092s6.391 14.275 14.275 14.275h350.982c7.883 0 14.275-6.391 14.275-14.275-.001-7.883-6.391-14.274-14.275-14.274z"/></svg>';
            } else if ($oc->NAME === 'Dehnen') {
                $content->svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><title>Dehnen</title><path d="M163.954 321.976l-150.54 78.661c-12.226 6.388-16.956 21.476-10.569 33.702 4.458 8.532 13.152 13.414 22.156 13.414 3.896 0 7.853-.916 11.545-2.845l148.653-77.675-21.245-45.257zM498.586 400.637l-150.54-78.661-21.245 45.256 148.653 77.675a24.864 24.864 0 0 0 11.545 2.845c9.002 0 17.698-4.883 22.156-13.414 6.387-12.225 1.657-27.313-10.569-33.701zM352.527 267.372l-24.589-87.983c-2.147-7.686-8.679-12.967-16.131-13.895l-110.239-.107c-8.013.414-15.229 5.847-17.508 14.001l-24.589 87.983a19.197 19.197 0 0 0 1.112 13.33l53.076 113.063c4.515 9.62 15.962 13.725 25.548 9.226 9.574-4.495 13.733-15.943 9.225-25.548l-.108-.23-49.936-106.372 18.78-67.196-5.342 61.728 36.524 77.806h15.298l24.419-52.019 12.105-25.787-5.342-61.728 18.78 67.196-50.044 106.604c-4.508 9.602-.378 21.041 9.225 25.548 9.608 4.51 21.042.373 25.548-9.226l53.076-113.063a19.197 19.197 0 0 0 1.112-13.331z"/><circle cx="256.918" cy="107.382" r="43.135"/></svg>';
            } else if ($oc->NAME === 'Zombieball') {
                $content->svg_icon = '<svg viewBox="0 0 175 304" height="100%" xmlns="http://www.w3.org/2000/svg"><path d="M22.438.527a42.303 42.303 0 0 0-7.094.625c-3.042.526-6.296 1.268-8.688 3.219-2.458 2.004-3.875 5.097-5 8.062C.513 15.446.095 18.746 0 21.965c-.004 1.792 1.596 2.931 2.375 4.406l3.156 4.656c1.895.2 2.7 3.11 4.938 1.938.438-.23 1.143.53 1.594.843 1.872 1.304 2.802 3.815 3.718 6.625l-1.218 7.875h-.094s-5.025 2.938-6.344 5.375c-3.122 5.77-3.248 13.021-2.437 19.532.885 7.112 7.812 20.031 7.812 20.031l12.219 20.031c0 3.645 1.145 7.885-1.469 10.75l-12.219 33.719 14.719 4 .094 5.594-1.469 7.593c.538 1.213 1.098 2.417 1.656 3.625l.281 14.875c-.675 1.103-1.37 2.211-1.937 3.344v3.5l2.031-.875.032 1.344-1.625 2.687c.534 2.532 1.152 5.053 1.78 7.563l.094 4.906v6.844l2.907.469-6.344.5 5.875 1.937-6.844 53.75 2.375-.281-1.156 10.125c-.45 1.769-.949 3.596-1.5 5.531.39.712.344 1.368.625 2.125l-.344 2.906 3.5.125c.51.306 1.065.622 1.75.97a30.868 30.868 0 0 1 3.532-.75c5.32.23 12.081.573 19.156 1.124 3.265.628 6.545 1.357 9.812 2 0 0 3.951 1.469 5.75.219.124-.086.235-.179.344-.281 1.451.083 1.62-.532.813-2.563-.04-.173-.11-.325-.188-.5-.052-.122-.111-.275-.187-.406l-.063-.094c-2.012-3.248-10.862-8.794-17.156-12.469l-6.594-5.812-.125-4.407 4.094-.5 6.812 1.97-8.281-6.345 7.25-16.843 1.969-5.875v-2.469L57 242.746l1.406-2.219 1.625-2.594v-3.5l.719-1.937 2.563-1.782v-4.25l-.157-.593 1.157-3.125-.688-8.375-1.5-2.313 1.875-3.5 3.094-11.656 19.687 17.531 3.094 9.188.219 3.593 4.969 14.594 1.406 1.25 3.625 10.688-.094 1.125 1.125 8.312 2.375.656 1.594 4.657-.813 2.125 1.563.094 2.937 8.687 4.844-2 3.969 6.781.906 2.25s1.796 2.654 3.469 5.282l2.5 4.25.125.187c.045.082.072.175.125.25l1.719 2.938 2.125-1.563c1.363-.146 2.753-.77 3.625-1.812.387-.465 1.437-1.627 2.5-2.782 6.125-4.61 14.384-10.902 21.187-16.468 5.367-4.391 5.468-5.151 3.094-6.094h-.031c-.435-1.619-2.075-2.188-3.75-1.313-3.415-.845-9.22.932-12.97 2.344l-4.062.844-3.062-3.844 5.937-2.437-24.156-55.407c-1.135-1.436-2.148-3.092-3.125-4.843l-.406-1.625-1.031-1.032c-1.166-2.25-2.298-4.54-3.5-6.656v-2.281l-4.063-2.25-2.437-4.844-5.282-7.219v-3.312l-2.718-4.094-8.532-8.125-.531-.594 5.438-5.687-5.844-6.844c-19.738-27.85-15.325-29.484-15.325-59.056l33.387 6.775 4.907-5.875 1.468 10.281 6.344-6.844v3.907l-8.812 9.28 14.656-6.843-9.75 12.219 12.219-12.219 4.75-11.906h9.75s1.038.205 1.468.5c.6.411.76 1.355 1.407 1.687 1.264.652 4.25.282 4.25.282l1.5-.594 2.062 2 1.781 1.469s-.072 1.943.594 2.468c.337.266.966.292 1.281 0 .754-.696.25-3.062.25-3.062l-1.531-2.375-2.187-2.094c1.012-.843 1.366-1.072 1.187-2.469l1.188.907 3.28 3.156 1.938 3.844c.347.69.99-.344 1.125-.782.382-1.24-1.375-3.656-1.375-3.656l-3.062-2.969-2.094-1.593v-.875l2.781 1.78 2.969 3.157s1.153 3.938 2.281 3.281l.657-.406c1.237-.72-1.563-3.844-1.563-3.844l-2.875-3.468-3.156-2 .093-.47 4.47 2.75 2.468 3.47s1.198 3.502 2.125 2.718l.75-.625c.838-.708-1.188-3.093-1.188-3.093l-2.093-2.844-5.625-3.875-4.563-1.781h-3.281l-7.125.593-10.813.47L78.5 73.714l-4.813-2.688 1.875.719 13.688-2.438 18.062 1.3 8.313 2.138 4.844-2 13.056 4.945-7.463-7.008 17.907 5.5-10.407-7.03 13.813 8.03-3.503-12.693 3.065-6.588.5-.125 4.844 1.438c2.481-.158 4.928-.733 7.375-1.188l3.75 2.063c1.508.503 1.707 2.02 2.875 3.28s1.898.366 1.781-.5-1.021-1.877-1.53-2.812c-1.47-.735-2.03-2.202-3.5-2.937-1.33-.655-.204-1.056-.157-2.156 2.16.944 3.131 1.37 5.219 2.468 1.865.534 2.678 3.055 3.531 3.907s1.06-.755 1.031-1.532-.767-1.705-1.156-2.562c-2.678-2.11-5.76-3.747-8.781-5.344l1.531-1 4.25 1.25c.415.54.63 1.31.969 1.938.25 1.453 1.919 2.724 2.937 2.406s0-1.713 0-2.563c-1.172-1.506-2.484-2.743-3.656-4.25a7.279 7.279 0 0 0-1.594-.312c-.94.223-1.59-.365-1.937-1.219.863-.306 3.192.837 4.562 1.156 3.19.768 1.725 3.655 2.875 4.907s1.657-1.597 1.531-2.063c-.143-2.707-1.877-3.56-2.812-5.344l-7.563-2.28c-.808.074-1.625.352-2.437.5-3.818.684-7.414 2.214-11.031 3.562-1.819.78-3.686 1.328-5.594 1.812l-.313-1.062c-18.279 2.404-38.6.778-55.687 4.375l-7.344.5-46.501-8.813 9.314 3.063-1.407-1.594-1.593.187-3.157-4.468c2.796-2.575 7.313-1.048 8.657-4.75.418-1.153-.347-3.999-2.532-4.813.011-.043.025-.062.032-.125-.06.014-.127.047-.188.063l-.093-.032c-.123-.194-.213-.287-.25-.312.002.065.061.263.156.406-1.646.457-3.738 1.451-3.75.781-1.601-.397-.375-4.953 3.312-5.28-1.288-1.809-.799-2.98-.937-3.5 4.665-2.988.434-5.393-2.156-7.688-.337-3.65-1.8-6.966-4.375-9.5 1.129.103 2.404-.03 1-.97-2.228-1.622-4.629-3.339-7.47-3.562-2.29-.556-4.652-.785-7.03-.78z"/></svg>';
            }
            $content->save();
        }
    }

    function migrateTrainings()
    {
        $oldTrainings = DB::connection("mysqlOld")->table('TRAININGS')->where('CREATED_AUTOMATICLY', '0')->get();
        foreach ($oldTrainings as $ot) {
            $training = new Training();
            $startExploded = explode(":", $ot->START_TIME);
            $training->start = DateTime::createFromFormat('Y-m-d', $ot->DATE)->setTime($startExploded[0], $startExploded[1], 00);
            $endExploded = explode(":", $ot->END_TIME);
            $training->end = DateTime::createFromFormat('Y-m-d', $ot->DATE)->setTime($endExploded[0], $endExploded[1], 00);
            $training->comment = $ot->COMMENT;
            $training->location_id = DB::connection("mysqlOld")->table('LOCATIONS')->where('ID', $ot->LOCATION_ID)->first()->ID;
            $training->save();
            $oldParticipants = DB::connection("mysqlOld")->table('TRAINING_PARTICIPATION')->where('TRAINING_ID', $ot->ID)->get();
            foreach ($oldParticipants as $op) {
                $member = DB::connection("mysqlOld")->table('MEMBERS')->where('ID', $op->MEMBER_ID)->first();
                if ($member) {
                    $user = User::where('firstName', $member->FIRST_NAME)->where('familyName', $member->FAMILY_NAME)->first();
                    $training->participants()->attach($user, ['attend' => 1]);
                }
            }
            $oldTrainers = DB::connection("mysqlOld")->table('TRAINING_TRAINER')->where('TRAINING_ID', $ot->ID)->get();
            foreach ($oldTrainers as $otr) {
                $oldTrainer = DB::connection("mysqlOld")->table('TRAINERS')->where('ID', $otr->TRAINER_ID)->first();
                if ($oldTrainer) {
                    $trainer = User::where('firstName', $oldTrainer->FIRST_NAME)->where('familyName', $oldTrainer->FAMILY_NAME)->first();
                    $training->trainers()->attach($trainer);
                }
            }
            $oldGroups = DB::connection("mysqlOld")->table('TRAINING_GROUP')->where('TRAINING_ID', $ot->ID)->get();
            foreach ($oldGroups as $og) {
                $oldGroup = DB::connection("mysqlOld")->table('MEMBER_GROUPS')->where('ID', $og->GROUP_ID)->first();
                if ($oldGroup) {
                    $group = Group::whereName($oldGroup->NAME)->first();
                    $training->groups()->attach($group);
                }
            }
            $oldContents = DB::connection("mysqlOld")->table('TRAINING_TRAINING_CONTENT')->where('TRAINING_ID', $ot->ID)->get();
            foreach ($oldContents as $oc) {
                $oldContent = DB::connection("mysqlOld")->table('TRAINING_CONTENTS')->where('NAME_KEY', $oc->CONTENT_NAME_KEY)->first();
                if ($oldContent) {
                    $content = Content::whereName($oldContent->NAME)->first();
                    $training->contents()->attach($content);
                }
            }
        }

    }

    function migrateTrainers()
    {
        $members = DB::connection("mysqlOld")->table('TRAINERS')->get();
        $roleTrainer = Role::findByName('trainer');
        $roleMember = Role::findByName('member');

        foreach ($members as $member) {
            $user = new User();
            $user->firstName = $member->FIRST_NAME;
            $user->familyName = $member->FAMILY_NAME;
            $user->email = $member->EMAIL;
            $user->birthdate = $member->BIRTHDATE;
            $user->active = $member->ACTIVE;
            $user->registered = 0;

            $user->save();
            $user->assignRole($roleMember);
            $user->assignRole($roleTrainer);
        }

        $wettkampfturnerI = Group::whereName('Wettkampfturner I')->first();
        $wettkampfturnerII = Group::whereName('Wettkampfturner II')->first();

        //admins
        $user = User::whereEmail('bindersebastian@online.de')->first();
        $user->password = 'test';
        $user->approved = 1;
        $user->registered = 1;
        $user->save();
        $roleAdmin = Role::findByName('admin');
        $user->assignRole($roleAdmin);
        $user->assignRole($roleTrainer);
        $user->trainerGroups()->attach($wettkampfturnerI);
        $user->trainerGroups()->attach($wettkampfturnerII);

        $user = User::whereEmail('michael.kindlein@outlook.com')->first();
        $user->password = 'test';
        $user->approved = 1;
        $user->save();
        $roleAdmin = Role::findByName('admin');
        $user->assignRole($roleAdmin);
        $user->assignRole($roleTrainer);
        $user->trainerGroups()->attach($wettkampfturnerI);
        $user->trainerGroups()->attach($wettkampfturnerII);

        $user = User::whereEmail('maximilian.kindlein@googlemail.com')->first();
        $user->password = 'test';
        $user->approved = 1;
        $user->save();
        $roleAdmin = Role::findByName('admin');
        $user->assignRole($roleAdmin);
        $user->assignRole($roleTrainer);
        $user->trainerGroups()->attach($wettkampfturnerI);
        $user->trainerGroups()->attach($wettkampfturnerII);

        //user
        $user = User::whereEmail('julian.binder@online.de')->first();
        $user->password = 'test';
        $user->approved = 1;
        $user->registered = 1;
        $user->save();

    }
}
