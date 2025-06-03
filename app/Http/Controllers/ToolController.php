<?php

namespace App\Http\Controllers;

// ... (use文は変更なし) ...
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;


class ToolController extends Controller
{
    public function index(): View
    {
        // ツール一覧ページ全体の表示権限をチェック
        $this->authorize('tools.viewAnyPage'); // ★ AuthServiceProviderで定義したGate名に変更

        $availableTools = [];
        $user = Auth::user();

        // 営業ツール
        if ($user->can('tools.sales.access')) { // ★ 営業ツールへの包括的アクセス権限でチェック
            $availableTools[] = [
                'name' => '営業ツール',
                'route' => route('tools.sales.index'),
                'description' => 'メールリスト管理、条件を絞ったメール送信、送信メールの閲覧状況の確認などが行えます。',
                'icon_class' => 'fa-envelope-open-text',
                'icon_bg_color_class' => 'bg-blue-500',
                'button_bg_color_class' => 'bg-blue-600',
                'button_hover_bg_color_class' => 'hover:bg-blue-700',
                'button_active_bg_color_class' => 'active:bg-blue-800',
            ];
        }
        // ... (他のツールも同様) ...

        return view('tools.index', [
            'availableTools' => $availableTools,
        ]);
    }
}
