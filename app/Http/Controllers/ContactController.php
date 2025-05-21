<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Services\ContentMasterService;
use App\Services\ContentDataService;
use App\Models\GeneralDefinition;
use App\Mail\ContactSendmail;


class ContactController extends Controller
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
        $logoImg = Image::where('VIEW_FLG', 'HP_999')->where('PRIORITY', 1)->first();
        $logoMinImg = Image::where('VIEW_FLG', 'HP_999')->where('PRIORITY', 2)->first();
        $XBtn = Image::where('VIEW_FLG', 'HP_007')->first();
        $backImg = Image::where('VIEW_FLG', 'HP_005')->first();

        $titleContact = Image::where('VIEW_FLG', 'HP_015')->first();

        $contactBtn = Image::where('VIEW_FLG', 'HP_601')->first();

        // お問い合わせフォーム（T003）のスキーマを取得
        $formFields = $this->contentData->getSchemaByMasterId('T003', true, true);

        return view('contact', compact(
            'logoImg',
            'logoMinImg',
            'XBtn',
            'backImg',
            'titleContact',
            'contactBtn',
            'formFields'
        ));
    }
    /**
     * フォーム送信処理の例
     */
    public function confirmForm(Request $request)
    {
        $logoImg = Image::where('VIEW_FLG', 'HP_999')->where('PRIORITY', 1)->first();
        $logoMinImg = Image::where('VIEW_FLG', 'HP_999')->where('PRIORITY', 2)->first();
        $XBtn = Image::where('VIEW_FLG', 'HP_007')->first();
        $backImg = Image::where('VIEW_FLG', 'HP_005')->first();

        $titleContact = Image::where('VIEW_FLG', 'HP_015')->first();

        $submitBtn = Image::where('VIEW_FLG', 'HP_602')->first();
        $backBtn = Image::where('VIEW_FLG', 'HP_603')->first();

        // スキーマを取得してバリデーションルールを動的に生成
        $schema = $this->contentData->getSchemaByMasterId('T003');
        $rules = [];
        $messages = [];

        foreach ($schema as $field) {
            $colName = $field['col_name'];
            $viewName = $field['view_name'];

            // 必須項目のバリデーション
            if (isset($field['required_flg']) && $field['required_flg'] === '1') {
                $rules[$colName] = 'required';
                $messages[$colName . '.required'] = $viewName . 'は必須項目です。';
            }

            // フィールドタイプに応じたバリデーション
            switch ($field['type']) {
                case 'email':
                    $rules[$colName] .= '|email';
                    $messages[$colName . '.email'] = $viewName . 'は正しいメールアドレス形式で入力してください。';
                    break;
                case 'tel':
                    $rules[$colName] .= '|regex:/^[0-9\-]+$/';
                    $messages[$colName . '.regex'] = $viewName . 'は数字とハイフンのみで入力してください。';
                    break;
            }
        }
        // バリデーション実行
        $validatedData = $request->validate($rules, $messages);

        $formData = $request->all();
        unset($formData['_token']); // CSRFトークンを除外

        // 入力データをセッションに保存
        session(['contact_input' => $formData]);

        // 確認画面に表示するためのフィールド情報を取得
        $formFields = $this->contentData->getSchemaByMasterId('T003', true, true);

        return view('contact-confirm', compact(
            'logoImg',
            'logoMinImg',
            'XBtn',
            'titleContact',
            'backImg',
            'submitBtn',
            'backBtn',
            'formFields',
            'formData'
        ));
    }

    /**
     * フォーム送信処理（ステップ3: 送信処理）
     */
    public function submitForm(Request $request)
    {
        // セッションから入力データを取得
        $input = session('contact_input');

        // セッションにデータがない場合は入力画面にリダイレクト
        if (!$input) {
            return redirect()->route('contact')->with('error', '入力データが見つかりません。もう一度入力してください。');
        }

        // データ保存処理
        $result = $this->contentData->store('T003', $input, '1');

        //ランダムなIDを生成し、問い合わせ番号とする
        $formFields = $this->contentData->getFieldSchema('T003', 'contact_category');

        foreach ($formFields["options"] as $option) {
            if ($option["value"] == $input["contact_category"]) {
                $code = $option["value"];
                $category = $option["label"];
            }
        }

        $referenceId = "";
        for ($i = 0; $i < 6; $i++) {
            $referenceId .= mt_rand(0, 9);
        }
        $dataId = $result["data_id"];
        $contentData = $this->contentData->getDataById($dataId);
        $content = $contentData->content ?? [];

        $referenceId = $code .  $dataId . $referenceId;
        $referenceId = substr($referenceId, 0, 8);

        $content["reference_number"] = $referenceId;

        $result = $this->contentData->update($dataId, $content);

        $content = $this->contentData->getDataById($dataId);

        //管理者に通知メールを知らせる
        $sendMail = GeneralDefinition::select('ITEM')->where('DEFINITION', '=', 'contact')->first();
        \Mail::send(new ContactSendmail($content["content"], $category, 'mail_kanri', $sendMail));
        //入力されたメールに返信する
        \Mail::send(new ContactSendmail($content["content"], $category, 'mail', null));

        // セッションから入力データを削除
        session()->forget('contact_input');

        if ($result['status'] === 'success') {
            // 完了画面にリダイレクト
            return redirect()->route('contact.complete');
        } else {
            return redirect()->route('contact')->with('error', $result['mess']);
        }
    }

    /**
     * 送信完了画面を表示する
     */
    public function completeForm()
    {
        $logoImg = Image::where('VIEW_FLG', 'HP_999')->where('PRIORITY', 1)->first();
        $logoMinImg = Image::where('VIEW_FLG', 'HP_999')->where('PRIORITY', 2)->first();
        $XBtn = Image::where('VIEW_FLG', 'HP_007')->first();
        $backImg = Image::where('VIEW_FLG', 'HP_005')->first();

        $titleContact = Image::where('VIEW_FLG', 'HP_015')->first();

        $topBackBtn = Image::where('VIEW_FLG', 'HP_604')->first();

        return view('contact-complete', compact(
            'logoImg',
            'logoMinImg',
            'XBtn',
            'titleContact',
            'backImg',
            'topBackBtn'
        ));
    }
}
