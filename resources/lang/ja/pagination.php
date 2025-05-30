<?php

// lang/ja/pagination.php

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    'previous' => '&laquo; 前へ',
    'next'     => '次へ &raquo;',

    // この部分を追加または修正します
    'showing' => ':total 件中 :first から :last まで表示',
    // もしくは、より自然な日本語として
    // 'showing_results' => '{1} :total 件中 :first から :last まで表示|[2,*] :total 件中 :first から :last まで表示',
    // よりシンプルな形式 (多くのケースでこちらが参照されることがあります)
    // 'results'      => ':total 件中 :first件目から:last件目を表示しています',

];
