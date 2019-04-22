<?php

use Illuminate\Database\Seeder;
use App\TrainingSeries;
use App\Location;
use App\User;
use App\Group;

class TrainingSeriesTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
      $samstagsTraining1 = new TrainingSeries();
      $samstagsTraining1->startTime = '09:00';
      $samstagsTraining1->endTime = '12:00';
      $samstagsTraining1->weekdays = '[6]';
      $samstagsTraining1->active = 1;
      $samstagsTraining1->location_id = Location::whereName('Dreifachturnhalle Landau')->first()->id;
      $samstagsTraining1->save();
      $samstagsTraining1->trainers()->attach(User::whereEmail('bindersebastian@online.de')->first());
      $samstagsTraining1->groups()->attach(Group::whereName('Wettkampfturner I')->first());
      $samstagsTraining1->groups()->attach(Group::whereName('Wettkampfturner II')->first());


      $freitagsTraining1 = new TrainingSeries();
      $freitagsTraining1->startTime = '17:30';
      $freitagsTraining1->endTime = '19:00';
      $freitagsTraining1->weekdays = '[5]';
      $freitagsTraining1->active = 1;
      $freitagsTraining1->location_id = Location::whereName('Gymnasium Landau')->first()->id;
      $freitagsTraining1->save();
      $freitagsTraining1->trainers()->attach(User::whereEmail('michael.kindlein@outlook.com')->first());
      $freitagsTraining1->trainers()->attach(User::whereEmail('hinkel993@web.de')->first());
      $freitagsTraining1->groups()->attach(Group::whereName('Wettkampfturner I')->first());
      $freitagsTraining1->groups()->attach(Group::whereName('Wettkampfturner II')->first());

      $mittwochsTraining = new TrainingSeries();
      $mittwochsTraining->startTime = '17:30';
      $mittwochsTraining->endTime = '19:00';
      $mittwochsTraining->weekdays = '[3]';
      $mittwochsTraining->active = 1;
      $mittwochsTraining->location_id = Location::whereName('Gymnasium Landau')->first()->id;
      $mittwochsTraining->save();
      $mittwochsTraining->trainers()->attach(User::whereEmail('michael.kindlein@outlook.com')->first());
      $mittwochsTraining->groups()->attach(Group::whereName('Wettkampfturner I')->first());
      $mittwochsTraining->groups()->attach(Group::whereName('Wettkampfturner II')->first());

      $freitagsTraining2 = new TrainingSeries();
      $freitagsTraining2->startTime = '17:30';
      $freitagsTraining2->endTime = '19:00';
      $freitagsTraining2->weekdays = '[5]';
      $freitagsTraining2->active = 1;
      $freitagsTraining2->location_id = Location::whereName('Gymnasium Landau')->first()->id;
      $freitagsTraining2->save();
      $freitagsTraining2->trainers()->attach(User::whereEmail('georgmalerwein@web.de')->first());
      $freitagsTraining2->trainers()->attach(User::whereEmail('davidbauer1609@gmail.com')->first());
      $freitagsTraining2->trainers()->attach(User::whereEmail('julian.binder@online.de')->first());
      $freitagsTraining2->groups()->attach(Group::whereName('Wettkampfturner III')->first());


      $samstagsTraining2 = new TrainingSeries();
      $samstagsTraining2->startTime = '10:30';
      $samstagsTraining2->endTime = '12:00';
      $samstagsTraining2->weekdays = '[6]';
      $samstagsTraining2->active = 1;
      $samstagsTraining2->location_id = Location::whereName('Dreifachturnhalle Landau')->first()->id;
      $samstagsTraining2->save();
      $samstagsTraining2->trainers()->attach(User::whereEmail('georgmalerwein@web.de')->first());
      $samstagsTraining2->trainers()->attach(User::whereEmail('davidbauer1609@gmail.com')->first());
      $samstagsTraining2->trainers()->attach(User::whereEmail('julian.binder@online.de')->first());
      $samstagsTraining2->groups()->attach(Group::whereName('Wettkampfturner III')->first());

      Artisan::call('training:series');

  }
}
