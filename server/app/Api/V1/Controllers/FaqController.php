<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Faq;
use Illuminate\Support\Facades\Storage;

class FaqController extends Controller
{

  /**
   * Get users with pagination.
   *
   * @return Response
   */
  public function index()
  {
    return response()->json(Faq::all());
  }

  public function getAllInfoDocuments() {
      $allFiles = Storage::disk('public')->files('infos');
      $urls = [];
      $fitlered_files = array_filter($allFiles, function($str){
          return substr_compare($str, ".pdf", -strlen(".pdf")) === 0;
      });
      foreach ($fitlered_files as $file) {
          array_push($urls, $file);
      }
      return response()->json($urls);

  }

}
