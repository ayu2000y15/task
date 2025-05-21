<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\ViewFlag;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AdminPhotoController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request)
    {
        $accessId = Session::get('access_id');
        if ($accessId == "0") {
            $viewFlg = ViewFlag::orderBy('view_flg')->get();

            $photos = DB::table('images as img')
                ->select(
                    'img.image_id as image_id',
                    'img.file_name as file_name',
                    'img.file_path as file_path',
                    'img.view_flg as view_flg',
                    'img.alt as alt',
                    'img.priority as priority',
                    'img.created_at as created_at',
                    'view.comment as v_comment'
                )
                ->join('view_flags as view', 'img.view_flg', '=', 'view.view_flg')
                ->orderBy('img.view_flg')
                ->orderByRaw('img.priority is null')
                ->orderByRaw('img.priority = 0')
                ->orderBy('img.priority')
                ->orderBy('img.image_id')
                ->get();
        } else {
            $viewFlg = ViewFlag::where("spare1", "1")
                ->orderBy('view_flg')->get();

            $photos = DB::table('images as img')
                ->select(
                    'img.image_id as image_id',
                    'img.file_name as file_name',
                    'img.file_path as file_path',
                    'img.view_flg as view_flg',
                    'img.alt as alt',
                    'img.priority as priority',
                    'img.created_at as created_at',
                    'view.comment as v_comment'
                )
                ->join('view_flags as view', 'img.view_flg', '=', 'view.view_flg')
                ->where("view.spare1", "1")
                ->orderBy('img.view_flg')
                ->orderByRaw('img.priority is null')
                ->orderByRaw('img.priority = 0')
                ->orderBy('img.priority')
                ->orderBy('img.image_id')
                ->get();
        }
        $this->fileCheck($viewFlg);

        return view('admin.photo', compact('viewFlg', 'photos'));
    }

    public function store(Request $request)
    {
        $uploadedFiles = $request->file('IMAGE');

        $filePath = 'img/hp';

        $result = $this->fileUploadService->uploadFiles($uploadedFiles, $filePath, $request);
        if ($result['success']) {
            return redirect()->route('admin.photo')
                ->with('success', 'ファイルが正常にアップロードされました。');
        } else {
            return redirect()->route('admin.photo')
                ->with('error', 'ファイルのアップロードに失敗しました: ' . $result['message']);
        }
    }

    public function edit($imageId)
    {
        $photo = DB::table('images')
            ->where('image_id', $imageId)
            ->where('delete_flg', '0')
            ->first();

        if (!$photo) {
            return redirect()->route('admin.photo')->with('error', '画像が見つかりません。');
        }

        $viewFlg = ViewFlag::orderBy('view_flg')->get();

        return view('admin.photo-edit', compact('photo', 'viewFlg'));
    }

    public function update(Request $request)
    {
        $img = Image::where('image_id', $request->image_id)->first();
        $img->update([
            'view_flg' => $request->view_flg,
            'alt' => $request->alt,
            'priority' => $request->priority
        ]);
        return redirect()->route('admin.photo')
            ->with('success', '画像の表示設定が変更されました。');
    }

    public function delete(Request $request)
    {
        $img = Image::where('image_id', $request->image_id)->first();
        if ($img) {
            $this->fileUploadService->deleteFile($img->file_path . $img->file_name);
            $img->delete();
            return redirect()->route('admin.photo')
                ->with('success', '画像が削除されました。');
        }
        return redirect()->route('admin.photo')
            ->with('error', '画像の削除に失敗しました。');
    }

    public function fileCheck($viewFlgList)
    {
        $cnt = 1;
        $error = [];

        foreach ($viewFlgList as $flg) {
            $f = $flg->view_flg;
            if ($f <> '00') {
                $imgCnt = Image::where('view_flg', $f)->count();
                $viewFlg = ViewFlag::where('view_flg', $f)->first();
                $maxCnt = $viewFlg->max_count;
                $comment = $viewFlg->comment;

                if ($maxCnt == 0) {
                    //max_countが0の場合、1枚も登録されていない場合はエラー
                    if ($imgCnt == 0) {
                        $error += [$f => ['error', '「' . $comment . '」の画像が1枚も登録されていません。1枚以上登録してください。']];
                    }
                } else {
                    //max_countが0の場合、1枚も登録されていない場合はエラー
                    if ($imgCnt == 0) {
                        $error += [$f => ['error', '「' . $comment . '」が1枚も登録されていません。1枚以上登録してください。']];
                    }

                    //max_countより多く登録されている場合はエラー
                    if ($imgCnt > $maxCnt) {
                        $error += [$f => ['error', '「' . $comment . '」が' . $maxCnt . '枚より多く登録されています。' . $maxCnt . '枚以内で登録してください。']];
                    }
                }
            }
            $cnt++;
        }
        Session::flash('errors', $error);
    }
}
