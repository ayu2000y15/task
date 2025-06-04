<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $email
 * @property string|null $reason
 * @property int|null $added_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $addedByUser
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereAddedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereUpdatedAt($value)
 */
	class BlacklistEntry extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string|null $description
 * @property string|null $gender
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cost> $costs
 * @property-read int|null $costs_count
 * @property-read string $gender_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Material> $materials
 * @property-read int|null $materials_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Measurement> $measurements
 * @property-read int|null $measurements_count
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Character whereUpdatedAt($value)
 */
	class Character extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property string $item_description
 * @property string $amount
 * @property string $type
 * @property \Illuminate\Support\Carbon $cost_date
 * @property string|null $notes 備考
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Character $character
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereCostDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereItemDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cost whereUpdatedAt($value)
 */
	class Cost extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SentEmail> $sentEmails
 * @property-read int|null $sent_emails_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscriber> $subscribers
 * @property-read int|null $subscribers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailList withoutTrashed()
 */
	class EmailList extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $sent_email_id
 * @property string $recipient_email
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SentEmail $sentEmail
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereClickedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereRecipientEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereSentEmailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTracking whereUserAgent($value)
 */
	class EmailTracking extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string|null $submitter_name 申請者名
 * @property string|null $submitter_email 申請者メールアドレス
 * @property string|null $submitter_notes 申請者からの備考
 * @property array<array-key, mixed> $submitted_data カスタムフィールドの入力データ
 * @property string $status 申請ステータス (new, processed, rejected)
 * @property int|null $processed_by_user_id
 * @property \Illuminate\Support\Carbon|null $processed_at 処理日時
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $processedBy
 * @property-read \App\Models\User|null $processor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereProcessedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereSubmittedData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereSubmitterEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereSubmitterName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereSubmitterNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalProjectSubmission whereUpdatedAt($value)
 */
	class ExternalProjectSubmission extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id 送信者ユーザーID
 * @property string $user_name 送信者名 (ユーザー情報からコピー)
 * @property string|null $email 連絡先メールアドレス
 * @property string|null $phone 連絡先電話番号
 * @property int|null $feedback_category_id
 * @property string $title タイトル
 * @property string $content 内容
 * @property int $priority 優先度: 1=高, 2=中, 3=低 など
 * @property string $status 対応ステータス: unread, not_started, in_progress, completed, cancelled, on_hold
 * @property string|null $assignee_text 対応者名 (自由記述)
 * @property \Illuminate\Support\Carbon|null $completed_at 完了日
 * @property string|null $admin_memo 管理者用メモ
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\FeedbackCategory|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FeedbackFile> $files
 * @property-read int|null $files_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereAdminMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereAssigneeText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereFeedbackCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Feedback whereUserName($value)
 */
	class Feedback extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property int $display_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Feedback> $feedbacks
 * @property-read int|null $feedbacks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackCategory whereUpdatedAt($value)
 */
	class FeedbackCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $feedback_id
 * @property string $file_path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int|null $size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Feedback $feedback
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile whereFeedbackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeedbackFile whereUpdatedAt($value)
 */
	class FeedbackFile extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name フィールドの内部名 (スラグ)
 * @property string $label 表示ラベル
 * @property string $type フィールドタイプ (text, textarea, date, number, select, checkbox)
 * @property array<array-key, mixed>|null $options select, radio, checkbox 用の選択肢 (JSONまたはカンマ区切り)
 * @property string|null $placeholder プレースホルダー
 * @property bool $is_required 必須フラグ
 * @property int $order 表示順
 * @property int|null $max_length 最大文字数など
 * @property bool $is_enabled 有効フラグ
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereMaxLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition wherePlaceholder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormFieldDefinition whereUpdatedAt($value)
 */
	class FormFieldDefinition extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereUpdatedAt($value)
 */
	class Holiday extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name 品名
 * @property string|null $description 説明
 * @property string $unit 単位 (例: m, 個, 袋)
 * @property numeric $total_cost 総原価
 * @property numeric $quantity 現在の在庫数
 * @property numeric $minimum_stock_level 最小在庫数/発注点
 * @property string|null $supplier 仕入先
 * @property \Illuminate\Support\Carbon|null $last_stocked_at 最終入荷日
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $average_unit_price
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereLastStockedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereMinimumStockLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereSupplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereTotalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereUpdatedAt($value)
 */
	class InventoryItem extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $inventory_item_id
 * @property int|null $user_id
 * @property string $change_type 変動種別 (例: stocked, used, adjusted, order_received, manual_stock_in)
 * @property numeric $quantity_change 変動量 (入荷・増加なら正、使用・減少なら負)
 * @property numeric $quantity_before_change 変動前の在庫数
 * @property numeric $quantity_after_change 変動後の在庫数
 * @property numeric|null $unit_price_at_change 変動時の単価
 * @property numeric|null $total_price_at_change 変動時の総額 (入荷時など)
 * @property int|null $related_material_id
 * @property int|null $related_stock_order_id
 * @property string|null $notes 備考 (理由、関連情報など)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\InventoryItem $inventoryItem
 * @property-read \App\Models\Material|null $material
 * @property-read \App\Models\StockOrder|null $stockOrder
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereChangeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereInventoryItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereQuantityAfterChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereQuantityBeforeChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereQuantityChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereRelatedMaterialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereRelatedStockOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereTotalPriceAtChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereUnitPriceAtChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLog whereUserId($value)
 */
	class InventoryLog extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $email
 * @property string|null $name
 * @property string|null $company_name
 * @property string|null $postal_code
 * @property string|null $address
 * @property string|null $phone_number
 * @property string|null $fax_number
 * @property string|null $url
 * @property string|null $representative_name
 * @property \Illuminate\Support\Carbon|null $establishment_date
 * @property string|null $industry
 * @property string|null $notes
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereEstablishmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereFaxNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereRepresentativeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagedContact whereUrl($value)
 */
	class ManagedContact extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property int|null $inventory_item_id
 * @property string $name
 * @property string|null $supplier
 * @property numeric|null $price
 * @property string|null $unit 単位 (在庫品目からコピー)
 * @property numeric|null $unit_price_at_creation 作成/購入時点の単価
 * @property numeric $quantity_needed
 * @property string $status
 * @property string|null $notes 備考
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Character $character
 * @property-read \App\Models\InventoryItem|null $inventoryItem
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereInventoryItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereQuantityNeeded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereSupplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereUnitPriceAtCreation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Material whereUpdatedAt($value)
 */
	class Material extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $character_id
 * @property string $item
 * @property string $value
 * @property string $unit
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Character $character
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement whereItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Measurement whereValue($value)
 */
	class Measurement extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name テンプレート名
 * @property string|null $description
 * @property array<array-key, mixed> $items 採寸項目 (item, notes の配列)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate whereItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeasurementTemplate whereUpdatedAt($value)
 */
	class MeasurementTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 */
	class Permission extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProcessTemplateItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplate whereUpdatedAt($value)
 */
	class ProcessTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $process_template_id
 * @property string $name
 * @property int|null $default_duration 分単位の標準工数
 * @property string|null $default_duration_unit 工数の単位 (days, hours, minutes)
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string|null $formatted_default_duration
 * @property-read \App\Models\ProcessTemplate $processTemplate
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem whereDefaultDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem whereDefaultDurationUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem whereProcessTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcessTemplateItem whereUpdatedAt($value)
 */
	class ProcessTemplateItem extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $title
 * @property string|null $series_title
 * @property string|null $client_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property bool $is_favorite
 * @property string|null $delivery_flag
 * @property string|null $payment_flag
 * @property string|null $payment
 * @property array<array-key, mixed>|null $form_definitions
 * @property array<array-key, mixed>|null $attributes
 * @property string|null $status
 * @property int|null $budget 予算
 * @property int|null $target_cost 目標コスト
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Character> $characters
 * @property-read int|null $characters_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasksWithoutCharacter
 * @property-read int|null $tasks_without_character_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereAttributes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereClientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeliveryFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereFormDefinitions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereIsFavorite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project wherePayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project wherePaymentFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSeriesTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTargetCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 */
	class Project extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $send_interval_minutes
 * @property int $emails_per_batch
 * @property bool $image_sending_enabled
 * @property int $batch_delay_seconds
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting whereBatchDelaySeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting whereEmailsPerBatch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting whereImageSendingEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting whereSendIntervalMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesToolSetting whereUpdatedAt($value)
 */
	class SalesToolSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $email_list_id
 * @property string $subject
 * @property string $body_html
 * @property string|null $body_text
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property string $sender_email
 * @property string|null $sender_name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\EmailList|null $emailList
 * @property-read string $readable_status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SentEmailLog> $recipientLogs
 * @property-read int|null $recipient_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmailTracking> $trackings
 * @property-read int|null $trackings_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereBodyHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereBodyText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereEmailListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereSenderEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereSenderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmail whereUpdatedAt($value)
 */
	class SentEmail extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $sent_email_id
 * @property int|null $subscriber_id
 * @property string $recipient_email
 * @property string|null $message_identifier
 * @property string|null $original_message_id
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $readable_status
 * @property-read \App\Models\SentEmail $sentEmail
 * @property-read \App\Models\Subscriber|null $subscriber
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereClickedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereDeliveredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereMessageIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereOriginalMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereRecipientEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereSentEmailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereSubscriberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SentEmailLog whereUpdatedAt($value)
 */
	class SentEmailLog extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $inventory_item_id
 * @property int|null $requested_by_user_id
 * @property numeric $quantity_requested 申請数量
 * @property string $status 申請ステータス: pending, approved, rejected, ordered, partially_received, received, cancelled
 * @property int|null $managed_by_user_id
 * @property \Illuminate\Support\Carbon|null $managed_at 最終対応日時
 * @property \Illuminate\Support\Carbon|null $expected_delivery_date 納品予定日
 * @property string|null $notes 申請者からの備考
 * @property string|null $manager_notes 管理者からの備考・メモ
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\InventoryItem $inventoryItem
 * @property-read \App\Models\User|null $managedByUser
 * @property-read \App\Models\User|null $requestedByUser
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereExpectedDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereInventoryItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereManagedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereManagedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereManagerNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereQuantityRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereRequestedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOrder whereUpdatedAt($value)
 */
	class StockOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $email_list_id
 * @property string $email
 * @property string|null $name
 * @property string|null $company_name
 * @property string|null $postal_code
 * @property string|null $address
 * @property string|null $phone_number
 * @property string|null $fax_number
 * @property string|null $url
 * @property string|null $representative_name
 * @property \Illuminate\Support\Carbon|null $establishment_date
 * @property string|null $industry
 * @property \Illuminate\Support\Carbon $subscribed_at
 * @property \Illuminate\Support\Carbon|null $unsubscribed_at
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\EmailList $emailList
 * @property-read string $readable_status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SentEmailLog> $sentEmailLogs
 * @property-read int|null $sent_email_logs_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereEmailListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereEstablishmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereFaxNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereRepresentativeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereSubscribedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereUnsubscribedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscriber whereUrl($value)
 */
	class Subscriber extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $character_id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property string|null $assignee
 * @property int|null $duration
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $status
 * @property int|null $progress
 * @property string $color
 * @property bool $is_milestone
 * @property bool $is_folder
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Character|null $character
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TaskFile> $files
 * @property-read int|null $files_count
 * @property-read string|null $formatted_duration
 * @property-read mixed $level
 * @property-read Task|null $parent
 * @property-read \App\Models\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereAssignee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCharacterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereIsFolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereIsMilestone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUpdatedAt($value)
 */
	class Task extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $task_id
 * @property string $original_name
 * @property string $stored_name
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Task $task
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile whereStoredName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskFile whereUpdatedAt($value)
 */
	class TaskFile extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

