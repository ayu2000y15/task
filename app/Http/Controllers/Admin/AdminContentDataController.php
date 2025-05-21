<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContentMasterService;
use App\Services\ContentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminContentDataController extends Controller
{
    protected $contentMaster;
    protected $contentData;

    public function __construct(ContentMasterService $contentMaster, ContentDataService $contentData)
    {
        $this->contentMaster = $contentMaster;
        $this->contentData = $contentData;
    }

    public function index()
    {
        $accessId = Session::get('access_id');
        if ($accessId == "0") {
            $masters = $this->contentMaster->getMasterAll();
            $allData = $this->contentData->getAllData();
        } else {
            $masters = $this->contentMaster->getMasterInId("'T001','T002','T003','T004','T006'");
            $allData = $this->contentData->getAllData();
        }

        return view('admin.content-data', compact('masters', 'allData'));
    }

    public function showByMaster($masterId)
    {
        $master = $this->contentMaster->getMasterById($masterId);

        if (!$master) {
            return redirect()->route('admin.content-data')
                ->with('error', 'マスターIDが見つかりません。');
        }

        $data = $this->contentData->getDataByMasterId($masterId);

        return view('admin.content-data-list', compact('master', 'data'));
    }

    public function create($masterId)
    {
        $master = $this->contentMaster->getMasterById($masterId);

        if (!$master) {
            return redirect()->route('admin.content-data')
                ->with('error', 'マスターIDが見つかりません。');
        }

        return view('admin.content-data-create', compact('master'));
    }

    public function store(Request $request, $masterId)
    {
        $master = $this->contentMaster->getMasterById($masterId);

        if (!$master) {
            return redirect()->route('admin.content-data')
                ->with('error', 'マスターIDが見つかりません。');
        }

        $content = [];
        $schema = $master->schema;

        foreach ($schema as $field) {
            $colName = $field['col_name'];

            if ($field['type'] == 'file') {
                // 単一ファイルの処理
                if ($request->hasFile($colName)) {
                    $file = $request->file($colName);
                    $path = $this->uploadFile($file, $masterId);
                    $content[$colName] = $path;
                }
            } elseif ($field['type'] == 'files') {
                // 複数ファイルの処理
                if ($request->hasFile($colName)) {
                    $files = $request->file($colName);
                    $paths = [];

                    foreach ($files as $file) {
                        $path = $this->uploadFile($file, $masterId);
                        $paths[] = $path;
                    }

                    $content[$colName] = $paths;
                }
            } elseif ($field['type'] == 'array') {
                // 配列フィールドの処理
                $arrayData = $request->input($colName, []);
                if (is_array($arrayData)) {
                    // 空の項目を削除
                    $filteredArray = [];
                    foreach ($arrayData as $item) {
                        if (!empty(array_filter($item))) {
                            $filteredArray[] = $item;
                        }
                    }
                    $content[$colName] = $filteredArray;
                } else {
                    $content[$colName] = [];
                }
            } elseif ($field['type'] == 'date' || $field['type'] == 'month') {
                // 日付フィールドの処理（空の場合は今日の日付を設定）
                $content[$colName] = $request->input($colName) ?: date('Y-m-d');
            } else {
                // 通常のフィールド処理
                $content[$colName] = $request->input($colName);
            }
        }

        $publicFlg = $request->input('public_flg', '0');
        $sortOrder = $request->input('sort_order', 0);

        $result = $this->contentData->store($masterId, $content, $publicFlg, $sortOrder);

        if ($result['status'] === 'success') {
            return redirect()->route('admin.content-data.master', ['masterId' => $masterId])
                ->with($result['status'], $result['mess']);
        } else {
            return redirect()->back()
                ->withInput()
                ->with($result['status'], $result['mess']);
        }
    }

    public function edit($dataId)
    {
        $contentData = $this->contentData->getDataById($dataId);

        if (!$contentData) {
            return redirect()->route('admin.content-data')
                ->with('error', 'データが見つかりません。');
        }

        $master = $this->contentMaster->getMasterById($contentData->master_id);

        if (!$master) {
            return redirect()->route('admin.content-data')
                ->with('error', 'マスターデータが見つかりません。');
        }

        return view('admin.content-data-edit', compact('contentData', 'master'));
    }

    public function update(Request $request, $dataId)
    {
        $contentData = $this->contentData->getDataById($dataId);

        if (!$contentData) {
            return redirect()->route('admin.content-data')
                ->with('error', 'データが見つかりません。');
        }

        $master = $this->contentMaster->getMasterById($contentData->master_id);

        if (!$master) {
            return redirect()->route('admin.content-data')
                ->with('error', 'マスターデータが見つかりません。');
        }

        $content = $contentData->content ?? [];
        $schema = $master->schema;

        foreach ($schema as $field) {
            $colName = $field['col_name'];

            if ($field['type'] == 'file') {
                // 単一ファイルの処理
                if ($request->hasFile($colName) && $request->file($colName)->isValid()) {
                    // 新しいファイルがアップロードされた場合
                    $file = $request->file($colName);
                    $path = $this->uploadFile($file, $contentData->master_id);

                    // 古いファイルを削除
                    if (isset($content[$colName]) && !empty($content[$colName])) {
                        $this->deleteFileFromStorage($content[$colName]);
                    }

                    $content[$colName] = $path;
                } elseif ($request->has($colName . '_delete')) {
                    // ファイル削除が指定された場合
                    if (isset($content[$colName]) && !empty($content[$colName])) {
                        $this->deleteFileFromStorage($content[$colName]);
                        $content[$colName] = null;
                    }
                } elseif ($request->has($colName . '_current')) {
                    // 現在のファイルを維持
                    $content[$colName] = $request->input($colName . '_current');
                }
            } elseif ($field['type'] == 'files') {
                // 複数ファイルの処理
                $currentFiles = isset($content[$colName]) && is_array($content[$colName]) ? $content[$colName] : [];

                // 削除対象のファイル
                if ($request->has($colName . '_delete_indexes')) {
                    $deleteIndexes = $request->input($colName . '_delete_indexes');

                    // 配列でない場合は配列に変換
                    if (!is_array($deleteIndexes)) {
                        $deleteIndexes = [$deleteIndexes];
                    }

                    // 数値に変換して降順にソート（高いインデックスから削除するため）
                    $deleteIndexes = array_map('intval', $deleteIndexes);
                    rsort($deleteIndexes);

                    foreach ($deleteIndexes as $index) {
                        if (isset($currentFiles[$index]) && !empty($currentFiles[$index])) {
                            $this->deleteFileFromStorage($currentFiles[$index]);
                            unset($currentFiles[$index]);
                        }
                    }

                    // インデックスを詰める
                    $currentFiles = array_values($currentFiles);
                }

                // 新しいファイルの追加
                if ($request->hasFile($colName)) {
                    $files = $request->file($colName);

                    // 単一ファイルが送信された場合は配列に変換
                    if (!is_array($files)) {
                        $files = [$files];
                    }

                    foreach ($files as $file) {
                        if ($file->isValid()) {
                            $path = $this->uploadFile($file, $contentData->master_id);
                            $currentFiles[] = $path;
                        }
                    }
                }

                $content[$colName] = $currentFiles;
            } elseif ($field['type'] == 'array') {
                // 配列フィールドの処理
                $arrayData = $request->input($colName, []);
                if (is_array($arrayData)) {
                    // 空の項目を削除
                    $filteredArray = [];
                    foreach ($arrayData as $item) {
                        if (!empty(array_filter($item))) {
                            $filteredArray[] = $item;
                        }
                    }
                    $content[$colName] = $filteredArray;
                } else {
                    $content[$colName] = [];
                }
            } elseif ($field['type'] == 'date' || $field['type'] == 'month') {
                // 日付フィールドの処理（空の場合は今日の日付を設定）
                $content[$colName] = $request->input($colName) ?: date('Y-m-d');
            } else {
                // 通常のフィールド処理
                $content[$colName] = $request->input($colName);
            }
        }

        $publicFlg = $request->input('public_flg');
        $sortOrder = $request->input('sort_order', $contentData->sort_order);

        $result = $this->contentData->update($dataId, $content, $publicFlg, $sortOrder);

        return redirect()->route('admin.content-data.master', ['masterId' => $contentData->master_id])
            ->with($result['status'], $result['mess']);
    }

    /**
     * ファイルを即時削除するAPI
     */
    public function deleteFile(Request $request, $dataId, $fieldName, $index = null)
    {
        // DELETEメソッドをエミュレートするための処理
        if ($request->isMethod('post') && $request->input('_method') === 'DELETE') {
            $request->setMethod('DELETE');
        }

        $contentData = $this->contentData->getDataById($dataId);

        if (!$contentData) {
            return response()->json(['status' => 'error', 'message' => 'データが見つかりません。'], 404);
        }

        $content = $contentData->content ?? [];

        if ($index !== null) {
            // 複数ファイルの場合
            if (!isset($content[$fieldName]) || !is_array($content[$fieldName]) || !isset($content[$fieldName][$index])) {
                return response()->json(['status' => 'error', 'message' => 'ファイルが見つかりません。'], 404);
            }

            $filePath = $content[$fieldName][$index];

            // 物理ファイルを削除
            $this->deleteFileFromStorage($filePath);

            // 配列から該当ファイルを削除
            unset($content[$fieldName][$index]);

            // インデックスを詰める
            $content[$fieldName] = array_values($content[$fieldName]);
        } else {
            // 単一ファイルの場合
            if (!isset($content[$fieldName]) || empty($content[$fieldName])) {
                return response()->json(['status' => 'error', 'message' => 'ファイルが見つかりません。'], 404);
            }

            $filePath = $content[$fieldName];

            // 物理ファイルを削除
            $this->deleteFileFromStorage($filePath);

            // ファイルパスをnullに設定
            $content[$fieldName] = null;
        }

        // データベースを更新
        $result = $this->contentData->updateContent($dataId, $content);

        if ($result['status'] === 'success') {
            return response()->json(['status' => 'success', 'message' => 'ファイルを削除しました。']);
        } else {
            return response()->json(['status' => 'error', 'message' => $result['mess']], 500);
        }
    }

    public function delete(Request $request, $dataId)
    {
        $contentData = $this->contentData->getDataById($dataId);

        if (!$contentData) {
            return redirect()->route('admin.content-data')
                ->with('error', 'データが見つかりません。');
        }

        $masterId = $contentData->master_id;

        // 関連ファイルの削除処理
        $this->deleteRelatedFiles($contentData);

        $result = $this->contentData->delete($dataId);

        return redirect()->route('admin.content-data.master', ['masterId' => $masterId])
            ->with($result['status'], $result['mess']);
    }

    /**
     * コンテンツデータの表示順を更新
     */
    public function updateOrder(Request $request, $masterId)
    {
        $validatedData = $request->validate([
            'sort_data' => 'required|json',
        ]);

        $sortData = json_decode($validatedData['sort_data'], true);

        $result = $this->contentData->updateOrder($sortData);

        return redirect()->route('admin.content-data.master', ['masterId' => $masterId])
            ->with($result['status'], $result['mess']);
    }

    /**
     * ファイルをアップロードして保存パスを返す
     */
    private function uploadFile($file, $masterId)
    {
        $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads/' . $masterId, $fileName, 'public');
        return str_replace('public/', '', 'storage/' . $path);
    }

    /**
     * ファイルを削除（ストレージからのみ）
     */
    private function deleteFileFromStorage($filePath)
    {
        if (empty($filePath)) {
            return;
        }

        $fullPath = 'public/storage/' . $filePath;

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    /**
     * コンテンツデータに関連するファイルを削除
     */
    private function deleteRelatedFiles($contentData)
    {
        if (!$contentData || !$contentData->content) {
            return;
        }

        $master = $this->contentMaster->getMasterById($contentData->master_id);
        if (!$master || !$master->schema) {
            return;
        }

        foreach ($master->schema as $field) {
            $colName = $field['col_name'];

            if ($field['type'] == 'file') {
                // 単一ファイルの削除
                if (isset($contentData->content[$colName]) && !empty($contentData->content[$colName])) {
                    $this->deleteFileFromStorage($contentData->content[$colName]);
                }
            } elseif ($field['type'] == 'files') {
                // 複数ファイルの削除
                if (isset($contentData->content[$colName]) && is_array($contentData->content[$colName])) {
                    foreach ($contentData->content[$colName] as $filePath) {
                        if (!empty($filePath)) {
                            $this->deleteFileFromStorage($filePath);
                        }
                    }
                }
            }
        }
    }

    /**
     * コンテンツデータの公開状態を切り替える
     */
    public function togglePublic(Request $request)
    {
        $validatedData = $request->validate([
            'data_id' => 'required|integer',
            'public_flg' => 'required|in:0,1',
        ]);

        $dataId = $validatedData['data_id'];
        $publicFlg = $validatedData['public_flg'];

        $contentData = $this->contentData->getDataById($dataId);

        if (!$contentData) {
            return response()->json([
                'status' => 'error',
                'message' => 'データが見つかりません。'
            ], 404);
        }

        // 公開状態を更新
        $result = $this->contentData->updatePublicStatus($dataId, $publicFlg);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $publicFlg == '1' ? 'データを公開しました。' : 'データを非公開にしました。'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['mess']
            ], 500);
        }
    }
}
