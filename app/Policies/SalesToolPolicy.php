<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesToolPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the tools listing page.
     * ツール一覧ページへの基本的な表示権限
     */
    public function viewAnyTools(User $user): bool
    {
        // 例: 'view_tools_page' のような汎用的な権限名を使用
        return $user->hasPermissionTo('tools.viewAnyPage'); // 権限名は適宜変更してください
    }

    /**
     * Determine whether the user can access the sales tool.
     * 営業ツール機能への包括的なアクセス権限
     */
    public function accessSalesTool(User $user): bool
    {
        // 例: 'access_sales_tool' のような権限名を使用
        return $user->hasPermissionTo('tools.sales.access'); // 権限名は適宜変更してください
    }

    // --- 以下、個別の機能権限メソッドは削除またはコメントアウト ---
    // public function manageMailLists(User $user): bool { ... }
    // public function sendMails(User $user): bool { ... }
    // public function viewMailStatus(User $user): bool { ... }
    // public function manageSettings(User $user): bool { ... }
    // public function viewAnySalesTool(User $user): bool { ... } // accessSalesTool に統合
}
