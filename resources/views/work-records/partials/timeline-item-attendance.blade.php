<div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
    <p class="font-semibold text-gray-800 dark:text-gray-100">
       @switch($log->type)
           @case('clock_in') <span class="text-green-600 dark:text-green-400"><i class="fas fa-sign-in-alt fa-fw mr-1"></i>出勤</span> @break
           @case('clock_out') <span class="text-red-600 dark:text-red-400"><i class="fas fa-sign-out-alt fa-fw mr-1"></i>退勤</span> @break
           @case('break_start') <span class="text-yellow-600 dark:text-yellow-400"><i class="fas fa-mug-hot fa-fw mr-1"></i>休憩開始</span> @break
           @case('break_end') <span class="text-yellow-600 dark:text-yellow-400"><i class="fas fa-mug-hot fa-fw mr-1"></i>休憩終了</span> @break
           @case('away_start') <span class="text-purple-600 dark:text-purple-400"><i class="fas fa-walking fa-fw mr-1"></i>中抜け開始</span> @break
           @case('away_end') <span class="text-purple-600 dark:text-purple-400"><i class="fas fa-walking fa-fw mr-1"></i>中抜け終了</span> @break
       @endswitch
   </p>
   @if($log->memo)
   <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
       メモ: {{ $log->memo }}
   </p>
   @endif
</div>