@extends('layouts.admin')

@section('title', 'HPテキスト管理')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center page-title mb-4">
            <h2>HPテキスト管理</h2>
            @if (session('access_id') == '0')
                <button type="button" class="btn btn-primary" id="newEntryBtn">
                    <i class="fas fa-plus me-1"></i> 新規登録
                </button>
            @else
                <button style="display: none;" type="button" class="btn btn-primary" id="newEntryBtn">
                    <i class="fas fa-plus me-1"></i> 新規登録
                </button>
            @endif
        </div>

        <!-- 登録・更新フォーム -->
        <div class="card mb-4" id="dataForm" style="display: none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">登録・更新</h5>
                <button type="button" class="btn-close" id="cancelBtn" aria-label="閉じる"></button>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.hptext.store') }}" method="POST" class="data-form">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="text_id" class="form-label">ID<span class="text-danger ms-1">*</span></label>
                            <input type="text" id="text_id" name="hp_text_id" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="memo" class="form-label">タイトル、メモ<span class="text-danger ms-1"></span></label>
                            <input type="text" id="memo" name="memo" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">内容<span class="text-danger ms-1">*</span></label>

                        <div class="rich-text-container">
                            <div class="rich-text-toolbar">
                                <div class="toolbar-left">
                                    <!-- フォントサイズ -->
                                    <select id="fontSizeSelect" class="format-select">
                                        <option value="1">10px</option>
                                        <option value="2">13px</option>
                                        <option value="3" selected>16px</option>
                                        <option value="4">18px</option>
                                        <option value="5">24px</option>
                                        <option value="6">32px</option>
                                        <option value="7">48px</option>
                                    </select>
                                    <select id="formatBlock" class="format-select">
                                        <option value="">書式</option>
                                        <option value="p">段落</option>
                                        <option value="h1">見出し 1</option>
                                        <option value="h2">見出し 2</option>
                                        <option value="h3">見出し 3</option>
                                        <option value="h4">見出し 4</option>
                                    </select>

                                    <button type="button" data-command="bold" class="toolbar-btn" title="太字">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" data-command="italic" class="toolbar-btn" title="斜体">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" data-command="underline" class="toolbar-btn" title="下線">
                                        <i class="fas fa-underline"></i>
                                    </button>
                                    <span class="toolbar-divider"></span>

                                    <button type="button" data-command="foreColor" class="toolbar-btn color-btn"
                                        title="文字色">
                                        <i class="fas fa-font"></i>
                                        <input type="color" class="color-picker" id="foreColorPicker" value="#000000">
                                    </button>
                                    <button type="button" data-command="backColor" class="toolbar-btn bg-color-btn"
                                        title="背景色">
                                        <i class="fas fa-fill-drip"></i>
                                        <input type="color" class="color-picker" id="backColorPicker" value="#ffffff">
                                    </button>
                                    <span class="toolbar-divider"></span>

                                    <button type="button" data-command="justifyLeft" class="toolbar-btn" title="左揃え">
                                        <i class="fas fa-align-left"></i>
                                    </button>
                                    <button type="button" data-command="justifyCenter" class="toolbar-btn" title="中央揃え">
                                        <i class="fas fa-align-center"></i>
                                    </button>
                                    <button type="button" data-command="justifyRight" class="toolbar-btn" title="右揃え">
                                        <i class="fas fa-align-right"></i>
                                    </button>
                                    <span class="toolbar-divider"></span>

                                    <button type="button" data-command="insertUnorderedList" class="toolbar-btn"
                                        title="箇条書き">
                                        <i class="fas fa-list-ul"></i>
                                    </button>
                                    <button type="button" data-command="insertOrderedList" class="toolbar-btn"
                                        title="番号付きリスト">
                                        <i class="fas fa-list-ol"></i>
                                    </button>
                                    <span class="toolbar-divider"></span>

                                    <button type="button" data-command="createLink" class="toolbar-btn" title="リンク">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <button type="button" data-command="insertImage" class="toolbar-btn" title="画像">
                                        <i class="fas fa-image"></i>
                                    </button>
                                </div>
                                <div class="toolbar-right">
                                    <span>フォーマット：</span>
                                    <select id="formatSelector" class="format-select">
                                        <option value="richtext" selected>リッチテキスト</option>
                                        <option value="plaintext">本文</option>
                                    </select>
                                </div>
                            </div>

                            <div id="editor" class="rich-text-editor" contenteditable="true"></div>
                            <textarea id="content" name="content" style="display: none;" required></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" onclick="return confirm('登録しますか？');" class="btn btn-primary"
                            id="submitBtn">登録</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- データ一覧 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">登録済みデータ一覧</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 150px;">操作</th>
                                <th>ID</th>
                                <th>タイトル、メモ</th>
                                <th>内容</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hpText as $def)
                                <tr>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-warning edit-btn" data-id="{{ $def->t_id }}"
                                                data-raw-content="{{ $def->content }}" data-memo="{{ $def->memo }}">
                                                <i class="fas fa-edit"></i> 編集
                                            </button>
                                            <form action="{{ route('admin.hptext.delete') }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="hp_text_id" value="{{ $def->t_id }}">
                                                @if (session('access_id') == '0')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('本当に削除しますか？');">
                                                        <i class="fas fa-trash"></i> 削除
                                                    </button>
                                                @else
                                                    <button style="display:none;" type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('本当に削除しますか？');">
                                                        <i class="fas fa-trash"></i> 削除
                                                    </button>
                                                @endif
                                            </form>
                                        </div>
                                    </td>
                                    <td>{{ $def->t_id }}</td>
                                    <td>{{ $def->memo }}</td>
                                    <td>
                                        <div class="rich-text-content">
                                            {!! $def->content !!}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons = document.querySelectorAll('.edit-btn');
            const form = document.querySelector('.data-form');
            const dataFormContainer = document.getElementById('dataForm');
            const newEntryBtn = document.getElementById('newEntryBtn');
            const submitBtn = document.getElementById('submitBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const formatSelector = document.getElementById('formatSelector');
            const editor = document.getElementById('editor');
            const contentTextarea = document.getElementById('content');
            const foreColorPicker = document.getElementById('foreColorPicker');
            const backColorPicker = document.getElementById('backColorPicker');

            // リッチテキストエディタの初期化
            initRichTextEditor();

            function initRichTextEditor() {
                const toolbarButtons = document.querySelectorAll('.toolbar-btn');
                const formatBlockSelect = document.getElementById('formatBlock');

                // ツールバーボタンのイベントリスナー
                toolbarButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        const command = this.dataset.command;
                        let value = this.dataset.value || '';

                        if (command === 'createLink') {
                            const url = prompt('リンクURLを入力してください:', 'https://');
                            if (url) {
                                document.execCommand(command, false, url);
                            }
                        } else if (command === 'insertImage') {
                            const url = prompt('画像URLを入力してください:', 'https://');
                            if (url) {
                                document.execCommand(command, false, url);
                            }
                        } else if (command === 'foreColor' || command === 'backColor') {
                            // カラーピッカーのクリックは処理しない（input[type=color]のchangeイベントで処理）
                            return;
                        } else {
                            document.execCommand(command, false, value);
                        }

                        // エディタの内容をテキストエリアに反映
                        updateTextarea();
                    });
                });

                // カラーピッカーのイベントリスナー
                foreColorPicker.addEventListener('input', function () {
                    document.execCommand('foreColor', false, this.value);
                    updateTextarea();
                });

                backColorPicker.addEventListener('input', function () {
                    document.execCommand('backColor', false, this.value);
                    updateTextarea();
                });

                // 書式選択のイベントリスナー
                formatBlockSelect.addEventListener('change', function () {
                    if (this.value) {
                        document.execCommand('formatBlock', false, '<' + this.value + '>');
                        this.selectedIndex = 0; // リセット
                        updateTextarea();
                    }
                });

                // エディタの内容変更イベント
                editor.addEventListener('input', updateTextarea);
                editor.addEventListener('blur', updateTextarea);

                // フォーマット選択の変更イベント
                formatSelector.addEventListener('change', function () {
                    if (this.value === 'plaintext') {
                        // プレーンテキストモード
                        editor.style.fontFamily = 'monospace';
                        document.querySelectorAll('.toolbar-btn, #formatBlock').forEach(el => {
                            el.disabled = true;
                        });
                    } else {
                        // リッチテキストモード
                        editor.style.fontFamily = '';
                        document.querySelectorAll('.toolbar-btn, #formatBlock').forEach(el => {
                            el.disabled = false;
                        });
                    }
                });
            }

            // エディタの内容をテキストエリアに反映する関数
            function updateTextarea() {
                contentTextarea.value = editor.innerHTML;
            }

            // テキストエリアの内容をエディタに反映する関数
            function updateEditor() {
                editor.innerHTML = contentTextarea.value;
            }

            // フォーム送信前にテキストエリアを更新
            form.addEventListener('submit', function () {
                updateTextarea();
            });

            //キャンセルボタンのイベントリスナー
            cancelBtn.addEventListener('click', function () {
                hideForm();
            });

            // 新規登録ボタンのイベントリスナー
            newEntryBtn.addEventListener('click', function () {
                resetForm();
                showForm();
            });

            // 編集ボタンのイベントリスナー
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const textId = this.getAttribute('data-id');
                    const rawContent = this.getAttribute('data-raw-content');
                    const memo = this.getAttribute('data-memo');

                    const textIdField = document.getElementById('text_id');
                    textIdField.value = textId;
                    textIdField.readOnly = true;
                    textIdField.classList.add('bg-light');

                    document.getElementById('memo').value = memo;

                    // エディタに内容を反映（生のHTMLを使用）
                    editor.innerHTML = rawContent;
                    // テキストエリアにも反映
                    contentTextarea.value = rawContent;

                    submitBtn.textContent = '更新';
                    form.action = "{{ route('admin.hptext.update') }}";

                    showForm();
                });
            });

            function resetForm() {
                form.reset();
                const textIdField = document.getElementById('text_id');
                textIdField.value = '';
                textIdField.readOnly = false;
                textIdField.classList.remove('bg-light');

                contentTextarea.value = '';
                editor.innerHTML = '';
                document.getElementById('memo').value = '';

                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> 登録';
                form.action = "{{ route('admin.hptext.store') }}";
            }

            function showForm() {
                dataFormContainer.style.display = 'block';
                dataFormContainer.scrollIntoView({ behavior: 'smooth' });
            }

            function hideForm() {
                dataFormContainer.style.display = 'none';
            }
        });

        // フォントサイズ変更
        document.getElementById('fontSizeSelect').addEventListener('change', function () {
            document.execCommand('fontSize', false, this.value);
        });
    </script>

    <style>
        .rich-text-editor font[size="1"] {
            font-size: 10px;
        }

        .rich-text-editor font[size="2"] {
            font-size: 13px;
        }

        .rich-text-editor font[size="3"] {
            font-size: 16px;
        }

        .rich-text-editor font[size="4"] {
            font-size: 18px;
        }

        .rich-text-editor font[size="5"] {
            font-size: 24px;
        }

        .rich-text-editor font[size="6"] {
            font-size: 32px;
        }

        .rich-text-editor font[size="7"] {
            font-size: 48px;
        }

        .rich-text-editor img {
            max-width: 80%;
            height: auto;
            display: block;
            margin: 1em 0;
        }

        .rich-text-content img {
            max-width: 80%;
            height: auto;
            display: block;
            margin: 1em 0;
        }
    </style>
@endsection