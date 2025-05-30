<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * When a model is being logged, an event will be dispatched. You can
     * listen for the Spatie\Activitylog\Events\ActivityWasCreated event
     * to execute custom logic when an activity is created.
     */
    'dispatches_events' => true,

    /*
     * The log name that will be used by default.
     */
    'default_log_name' => 'default',

    /*
     * The database connection that will be used to store the activities.
     * Null means the default connection will be used.
     */
    'database_connection' => null,

    /*
     * The name of the table that will be used to store the activities.
     */
    'table_name' => 'activity_log',

    /*
     * The model that will be used to represent an activity log.
     *
     * It must be or extend `Spatie\Activitylog\Models\Activity`.
     */
    'model' => \Spatie\Activitylog\Models\Activity::class,

    /*
     * This new column takes the subject model's UUID. It must be either
     * an Illuminate\Database\Eloquent\Concerns\HasUuids or Ramsey\Uuid\UuidInterface.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * This new column takes the subject model's UUID. It must be either
     * an Illuminate\Database\Eloquent\Concerns\HasUuids or Ramsey\Uuid\UuidInterface.
     */
    'activity_model_uses_uuid' => false,

    /*
     * This new column takes the subject model's UUID. It must be either
     * an Illuminate\Database\Eloquent\Concerns\HasUuids or Ramsey\Uuid\UuidInterface.
     */
    'causer_model_uses_uuid' => false,

    /*
     * Deleting records older than this amount of days.
     *
     * Note that this will only remove records created by this package.
     *
     * This setting assumes that there is a `created_at` column on your
     * activity log table. It must be something that can be parsed by
     * `Carbon\Carbon::parse()`.
     *
     * Leaving it null will disable the automatic cleanup.
     */
    // 要件に基づき、バッチで制御するためnullまたは大きな値にする
    'delete_records_older_than_days' => 366, // または 366 など

    /*
     * The default auth driver that will be used to guard against the causer.
     * The value should be the name of an auth driver.
     * When this is null we'll use the default driver from the auth config.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject will be saved as a relation.
     * This will result in a cleaner json output for the subject.
     */
    'subject_as_relation' => false,

    /*
     * If set to true, the causer will be saved as a relation.
     * This will result in a cleaner json output for the causer.
     */
    'causer_as_relation' => false,

    /*
     * If set to true, the properties will be saved as a relation.
     * This will result in a cleaner json output for the properties.
     */
    'properties_as_relation' => false,

    /*
     * If you want to override the default log profile.
     * You can use \Spatie\Activitylog\LogOptions::defaults() to get the default options.
     */
    'default_log_options' => null,
];
