<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col font-sans">
    <!-- Header -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-rose-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 font-bold text-white text-lg">
            4Q
          </div>
          <div>
            <h1 class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
              Eisenhower Matrix Tasks
              <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">JST 日本時間対応</span>
            </h1>
            <p class="text-xs text-slate-400">緊急度 × 重要度 4象限タスクマネージャー</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="fetchTasks"
            :disabled="isLoading"
            class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition duration-150 disabled:opacity-50"
            title="最新状態に更新"
          >
            <svg class="w-5 h-5" :class="{ 'animate-spin': isLoading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
          <button
            @click="openCreateModal('urgent_important')"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-rose-500 to-indigo-600 text-white shadow-lg shadow-rose-500/20 hover:from-rose-600 hover:to-indigo-700 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            新規タスク作成
          </button>
        </div>
      </div>
    </header>

    <!-- Error Alert Banner -->
    <div v-if="error" class="bg-rose-500/10 border-b border-rose-500/20 text-rose-300 px-4 py-2.5 text-sm flex justify-between items-center max-w-7xl mx-auto w-full">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span>{{ error }}</span>
      </div>
      <button @click="error = null" class="text-rose-400 hover:text-rose-200">×</button>
    </div>

    <!-- Main 4-Quadrant Matrix Layout -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 flex flex-col">
      <!-- Matrix Grid: 2 columns x 2 rows -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1">
        <!-- Quadrant 1: 緊急かつ重要 (DO / 赤) -->
        <div class="flex flex-col rounded-2xl border border-rose-500/30 bg-gradient-to-b from-rose-950/20 to-slate-900/60 shadow-xl overflow-hidden backdrop-blur">
          <div class="px-5 py-3.5 border-b border-rose-500/20 bg-rose-900/20 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-3 h-3 rounded-full bg-rose-500 animate-pulse"></span>
              <div>
                <h2 class="font-bold text-rose-100 flex items-center gap-2">
                  第1象限：緊急 × 重要
                  <span class="text-xs px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30">すぐやる (DO)</span>
                </h2>
                <p class="text-xs text-rose-300/70">危機、締切直前のタスク、最優先課題</p>
              </div>
            </div>
            <button
              @click="openCreateModal('urgent_important')"
              class="p-1.5 rounded-md hover:bg-rose-500/20 text-rose-300 hover:text-white transition"
              title="この象限にタスクを追加"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>

          <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
            <template v-if="tasksByQuadrant.urgent_important.length > 0">
              <div
                v-for="task in tasksByQuadrant.urgent_important"
                :key="task.id"
                class="group p-3.5 rounded-xl border border-rose-500/20 bg-slate-800/80 hover:bg-slate-800 hover:border-rose-500/40 transition shadow"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 flex-1 min-w-0">
                    <input
                      type="checkbox"
                      :checked="task.is_completed"
                      @change="toggleComplete(task)"
                      class="mt-1 h-4 w-4 rounded border-slate-700 text-rose-500 focus:ring-rose-400 bg-slate-900 cursor-pointer shrink-0"
                    />
                    <div class="flex-1 min-w-0 cursor-pointer" @click="openEditModal(task)">
                      <h3
                        class="text-sm font-semibold text-slate-100 transition truncate group-hover:text-rose-200"
                        :class="{ 'line-through text-slate-400': task.is_completed }"
                      >
                        {{ task.title }}
                      </h3>
                      <p v-if="task.description" class="text-xs text-slate-400 mt-1 whitespace-pre-wrap line-clamp-2">
                        {{ task.description }}
                      </p>
                      <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                        <span v-if="task.due_date" class="flex items-center gap-1 text-rose-300 font-medium">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                          {{ formatDate(task.due_date) }}
                        </span>
                        <span v-if="task.google_event_id" class="flex items-center gap-1 text-indigo-400 text-[10px] bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20">
                          Google Calendar 連携済
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Actions Dropdown / Edit / Delete -->
                  <div class="flex items-center gap-1 shrink-0">
                    <select
                      :value="task.priority_type"
                      @change="handleQuadrantChange(task, $event)"
                      class="text-xs bg-slate-900 border border-slate-700 text-slate-300 rounded px-1.5 py-1 focus:ring-1 focus:ring-rose-500"
                    >
                      <option value="urgent_important">第1象限 (赤)</option>
                      <option value="not_urgent_important">第2象限 (青)</option>
                      <option value="urgent_not_important">第3象限 (黄)</option>
                      <option value="not_urgent_not_important">第4象限 (灰)</option>
                    </select>
                    <!-- Edit Button -->
                    <button
                      @click="openEditModal(task)"
                      class="p-1.5 rounded text-slate-400 hover:text-white hover:bg-slate-700/60 transition"
                      title="内容を編集"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <!-- Delete Button -->
                    <button
                      @click="deleteTask(task.id)"
                      class="p-1.5 rounded text-slate-500 hover:text-rose-400 hover:bg-slate-700/60 transition"
                      title="削除"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </template>
            <div v-else class="h-36 flex flex-col items-center justify-center text-center text-slate-500 text-xs border border-dashed border-rose-500/20 rounded-xl">
              <p>緊急かつ重要なタスクはありません</p>
              <button @click="openCreateModal('urgent_important')" class="mt-2 text-rose-400 hover:underline">+ タスクを追加</button>
            </div>
          </div>
        </div>

        <!-- Quadrant 2: 緊急ではないが重要 (PLAN / 青) -->
        <div class="flex flex-col rounded-2xl border border-sky-500/30 bg-gradient-to-b from-sky-950/20 to-slate-900/60 shadow-xl overflow-hidden backdrop-blur">
          <div class="px-5 py-3.5 border-b border-sky-500/20 bg-sky-900/20 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-3 h-3 rounded-full bg-sky-500"></span>
              <div>
                <h2 class="font-bold text-sky-100 flex items-center gap-2">
                  第2象限：非緊急 × 重要
                  <span class="text-xs px-2 py-0.5 rounded bg-sky-500/20 text-sky-300 border border-sky-500/30">計画する (PLAN)</span>
                </h2>
                <p class="text-xs text-sky-300/70">長期目標、自己投資、健康管理、人間関係</p>
              </div>
            </div>
            <button
              @click="openCreateModal('not_urgent_important')"
              class="p-1.5 rounded-md hover:bg-sky-500/20 text-sky-300 hover:text-white transition"
              title="この象限にタスクを追加"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>

          <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
            <template v-if="tasksByQuadrant.not_urgent_important.length > 0">
              <div
                v-for="task in tasksByQuadrant.not_urgent_important"
                :key="task.id"
                class="group p-3.5 rounded-xl border border-sky-500/20 bg-slate-800/80 hover:bg-slate-800 hover:border-sky-500/40 transition shadow"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 flex-1 min-w-0">
                    <input
                      type="checkbox"
                      :checked="task.is_completed"
                      @change="toggleComplete(task)"
                      class="mt-1 h-4 w-4 rounded border-slate-700 text-sky-500 focus:ring-sky-400 bg-slate-900 cursor-pointer shrink-0"
                    />
                    <div class="flex-1 min-w-0 cursor-pointer" @click="openEditModal(task)">
                      <h3
                        class="text-sm font-semibold text-slate-100 transition truncate group-hover:text-sky-200"
                        :class="{ 'line-through text-slate-400': task.is_completed }"
                      >
                        {{ task.title }}
                      </h3>
                      <p v-if="task.description" class="text-xs text-slate-400 mt-1 whitespace-pre-wrap line-clamp-2">
                        {{ task.description }}
                      </p>
                      <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                        <span v-if="task.due_date" class="flex items-center gap-1 text-sky-300 font-medium">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                          {{ formatDate(task.due_date) }}
                        </span>
                        <span v-if="task.google_event_id" class="flex items-center gap-1 text-indigo-400 text-[10px] bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20">
                          Google Calendar 連携済
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center gap-1 shrink-0">
                    <select
                      :value="task.priority_type"
                      @change="handleQuadrantChange(task, $event)"
                      class="text-xs bg-slate-900 border border-slate-700 text-slate-300 rounded px-1.5 py-1 focus:ring-1 focus:ring-sky-500"
                    >
                      <option value="urgent_important">第1象限 (赤)</option>
                      <option value="not_urgent_important">第2象限 (青)</option>
                      <option value="urgent_not_important">第3象限 (黄)</option>
                      <option value="not_urgent_not_important">第4象限 (灰)</option>
                    </select>
                    <button
                      @click="openEditModal(task)"
                      class="p-1.5 rounded text-slate-400 hover:text-white hover:bg-slate-700/60 transition"
                      title="内容を編集"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click="deleteTask(task.id)"
                      class="p-1.5 rounded text-slate-500 hover:text-rose-400 hover:bg-slate-700/60 transition"
                      title="削除"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </template>
            <div v-else class="h-36 flex flex-col items-center justify-center text-center text-slate-500 text-xs border border-dashed border-sky-500/20 rounded-xl">
              <p>人生の質を高める第2象限の計画を追加しましょう</p>
              <button @click="openCreateModal('not_urgent_important')" class="mt-2 text-sky-400 hover:underline">+ タスクを追加</button>
            </div>
          </div>
        </div>

        <!-- Quadrant 3: 緊急だが重要ではない (DELEGATE / 黄) -->
        <div class="flex flex-col rounded-2xl border border-amber-500/30 bg-gradient-to-b from-amber-950/20 to-slate-900/60 shadow-xl overflow-hidden backdrop-blur">
          <div class="px-5 py-3.5 border-b border-amber-500/20 bg-amber-900/20 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-3 h-3 rounded-full bg-amber-500"></span>
              <div>
                <h2 class="font-bold text-amber-100 flex items-center gap-2">
                  第3象限：緊急 × 非重要
                  <span class="text-xs px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">委託する (DELEGATE)</span>
                </h2>
                <p class="text-xs text-amber-300/70">突然の割り込み、重要度の低い電話や会議</p>
              </div>
            </div>
            <button
              @click="openCreateModal('urgent_not_important')"
              class="p-1.5 rounded-md hover:bg-amber-500/20 text-amber-300 hover:text-white transition"
              title="この象限にタスクを追加"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>

          <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
            <template v-if="tasksByQuadrant.urgent_not_important.length > 0">
              <div
                v-for="task in tasksByQuadrant.urgent_not_important"
                :key="task.id"
                class="group p-3.5 rounded-xl border border-amber-500/20 bg-slate-800/80 hover:bg-slate-800 hover:border-amber-500/40 transition shadow"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 flex-1 min-w-0">
                    <input
                      type="checkbox"
                      :checked="task.is_completed"
                      @change="toggleComplete(task)"
                      class="mt-1 h-4 w-4 rounded border-slate-700 text-amber-500 focus:ring-amber-400 bg-slate-900 cursor-pointer shrink-0"
                    />
                    <div class="flex-1 min-w-0 cursor-pointer" @click="openEditModal(task)">
                      <h3
                        class="text-sm font-semibold text-slate-100 transition truncate group-hover:text-amber-200"
                        :class="{ 'line-through text-slate-400': task.is_completed }"
                      >
                        {{ task.title }}
                      </h3>
                      <p v-if="task.description" class="text-xs text-slate-400 mt-1 whitespace-pre-wrap line-clamp-2">
                        {{ task.description }}
                      </p>
                      <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                        <span v-if="task.due_date" class="flex items-center gap-1 text-amber-300 font-medium">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                          {{ formatDate(task.due_date) }}
                        </span>
                        <span v-if="task.google_event_id" class="flex items-center gap-1 text-indigo-400 text-[10px] bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20">
                          Google Calendar 連携済
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center gap-1 shrink-0">
                    <select
                      :value="task.priority_type"
                      @change="handleQuadrantChange(task, $event)"
                      class="text-xs bg-slate-900 border border-slate-700 text-slate-300 rounded px-1.5 py-1 focus:ring-1 focus:ring-amber-500"
                    >
                      <option value="urgent_important">第1象限 (赤)</option>
                      <option value="not_urgent_important">第2象限 (青)</option>
                      <option value="urgent_not_important">第3象限 (黄)</option>
                      <option value="not_urgent_not_important">第4象限 (灰)</option>
                    </select>
                    <button
                      @click="openEditModal(task)"
                      class="p-1.5 rounded text-slate-400 hover:text-white hover:bg-slate-700/60 transition"
                      title="内容を編集"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click="deleteTask(task.id)"
                      class="p-1.5 rounded text-slate-500 hover:text-rose-400 hover:bg-slate-700/60 transition"
                      title="削除"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </template>
            <div v-else class="h-36 flex flex-col items-center justify-center text-center text-slate-500 text-xs border border-dashed border-amber-500/20 rounded-xl">
              <p>誰かに任せられるタスクはありません</p>
              <button @click="openCreateModal('urgent_not_important')" class="mt-2 text-amber-400 hover:underline">+ タスクを追加</button>
            </div>
          </div>
        </div>

        <!-- Quadrant 4: 緊急でも重要でもない (ELIMINATE / グレー) -->
        <div class="flex flex-col rounded-2xl border border-slate-700 bg-gradient-to-b from-slate-800/40 to-slate-900/60 shadow-xl overflow-hidden backdrop-blur">
          <div class="px-5 py-3.5 border-b border-slate-700 bg-slate-800/40 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-3 h-3 rounded-full bg-slate-400"></span>
              <div>
                <h2 class="font-bold text-slate-200 flex items-center gap-2">
                  第4象限：非緊急 × 非重要
                  <span class="text-xs px-2 py-0.5 rounded bg-slate-700 text-slate-300 border border-slate-600">削減する (ELIMINATE)</span>
                </h2>
                <p class="text-xs text-slate-400">無駄な時間、過度のSNS・娯楽、単なる暇つぶし</p>
              </div>
            </div>
            <button
              @click="openCreateModal('not_urgent_not_important')"
              class="p-1.5 rounded-md hover:bg-slate-700 text-slate-300 hover:text-white transition"
              title="この象限にタスクを追加"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>

          <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
            <template v-if="tasksByQuadrant.not_urgent_not_important.length > 0">
              <div
                v-for="task in tasksByQuadrant.not_urgent_not_important"
                :key="task.id"
                class="group p-3.5 rounded-xl border border-slate-700/60 bg-slate-800/60 hover:bg-slate-800 hover:border-slate-600 transition shadow"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 flex-1 min-w-0">
                    <input
                      type="checkbox"
                      :checked="task.is_completed"
                      @change="toggleComplete(task)"
                      class="mt-1 h-4 w-4 rounded border-slate-700 text-slate-400 focus:ring-slate-400 bg-slate-900 cursor-pointer shrink-0"
                    />
                    <div class="flex-1 min-w-0 cursor-pointer" @click="openEditModal(task)">
                      <h3
                        class="text-sm font-semibold text-slate-300 transition truncate group-hover:text-white"
                        :class="{ 'line-through text-slate-500': task.is_completed }"
                      >
                        {{ task.title }}
                      </h3>
                      <p v-if="task.description" class="text-xs text-slate-500 mt-1 whitespace-pre-wrap line-clamp-2">
                        {{ task.description }}
                      </p>
                      <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                        <span v-if="task.due_date" class="flex items-center gap-1 text-slate-400">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                          {{ formatDate(task.due_date) }}
                        </span>
                        <span v-if="task.google_event_id" class="flex items-center gap-1 text-indigo-400 text-[10px] bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20">
                          Google Calendar 連携済
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center gap-1 shrink-0">
                    <select
                      :value="task.priority_type"
                      @change="handleQuadrantChange(task, $event)"
                      class="text-xs bg-slate-900 border border-slate-700 text-slate-300 rounded px-1.5 py-1 focus:ring-1 focus:ring-slate-400"
                    >
                      <option value="urgent_important">第1象限 (赤)</option>
                      <option value="not_urgent_important">第2象限 (青)</option>
                      <option value="urgent_not_important">第3象限 (黄)</option>
                      <option value="not_urgent_not_important">第4象限 (灰)</option>
                    </select>
                    <button
                      @click="openEditModal(task)"
                      class="p-1.5 rounded text-slate-400 hover:text-white hover:bg-slate-700/60 transition"
                      title="内容を編集"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      @click="deleteTask(task.id)"
                      class="p-1.5 rounded text-slate-500 hover:text-rose-400 hover:bg-slate-700/60 transition"
                      title="削除"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </template>
            <div v-else class="h-36 flex flex-col items-center justify-center text-center text-slate-500 text-xs border border-dashed border-slate-700 rounded-xl">
              <p>無駄なタスクはありません。素晴らしい！</p>
              <button @click="openCreateModal('not_urgent_not_important')" class="mt-2 text-slate-400 hover:underline">+ タスクを追加</button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Create Task Modal -->
    <div
      v-if="isCreateModalOpen"
      class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-800/50">
          <h3 class="font-bold text-white text-base">新規タスク追加</h3>
          <button @click="isCreateModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitCreateTask" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">タイトル *</label>
            <input
              v-model="createTaskForm.title"
              type="text"
              required
              placeholder="例: クライアント提案書の作成"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">詳細メモ</label>
            <textarea
              v-model="createTaskForm.description"
              rows="3"
              placeholder="タスクの概要、参照リンク、メモなど"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">優先度象限 *</label>
              <select
                v-model="createTaskForm.priority_type"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="urgent_important">第1象限: 緊急 × 重要 (赤)</option>
                <option value="not_urgent_important">第2象限: 非緊急 × 重要 (青)</option>
                <option value="urgent_not_important">第3象限: 緊急 × 非重要 (黄)</option>
                <option value="not_urgent_not_important">第4象限: 非緊急 × 非重要 (灰)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">締切日時 (日本時間)</label>
              <input
                v-model="createTaskForm.due_date"
                type="datetime-local"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div class="pt-2 flex justify-end gap-3 border-t border-slate-800">
            <button
              type="button"
              @click="isCreateModalOpen = false"
              class="px-4 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-800 transition"
            >
              キャンセル
            </button>
            <button
              type="submit"
              :disabled="isLoading || !createTaskForm.title"
              class="px-5 py-2 rounded-lg text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
            >
              タスクを登録
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Task Modal -->
    <div
      v-if="isEditModalOpen"
      class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-800/50">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
            <h3 class="font-bold text-white text-base">タスク内容の編集</h3>
          </div>
          <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitUpdateTask" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">タイトル *</label>
            <input
              v-model="editTaskForm.title"
              type="text"
              required
              placeholder="タスクのタイトル"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">詳細メモ</label>
            <textarea
              v-model="editTaskForm.description"
              rows="3"
              placeholder="タスクの概要、参照リンク、メモなど"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">優先度象限 *</label>
              <select
                v-model="editTaskForm.priority_type"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="urgent_important">第1象限: 緊急 × 重要 (赤)</option>
                <option value="not_urgent_important">第2象限: 非緊急 × 重要 (青)</option>
                <option value="urgent_not_important">第3象限: 緊急 × 非重要 (黄)</option>
                <option value="not_urgent_not_important">第4象限: 非緊急 × 非重要 (灰)</option>
              </select>
            </div>

            <div>
              <div class="flex justify-between items-center mb-1.5">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">締切日時 (日本時間)</label>
                <button
                  v-if="editTaskForm.due_date"
                  type="button"
                  @click="editTaskForm.due_date = ''"
                  class="text-[11px] text-slate-400 hover:text-rose-400 underline"
                >
                  解除
                </button>
              </div>
              <input
                v-model="editTaskForm.due_date"
                type="datetime-local"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div class="pt-2 flex justify-between items-center border-t border-slate-800">
            <button
              type="button"
              @click="deleteCurrentEditingTask"
              class="px-3 py-2 rounded-lg text-sm text-rose-400 hover:bg-rose-500/10 transition flex items-center gap-1.5"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              タスク削除
            </button>
            <div class="flex gap-3">
              <button
                type="button"
                @click="isEditModalOpen = false"
                class="px-4 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-800 transition"
              >
                キャンセル
              </button>
              <button
                type="submit"
                :disabled="isLoading || !editTaskForm.title"
                class="px-5 py-2 rounded-lg text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
              >
                変更を保存
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useTasks } from '~/composables/useTasks';
import type { PriorityType, Task } from '~/types/task';

useHead({
  title: '4象限タスクマネージャー | Nuxt 3 × Laravel 11',
  meta: [
    { name: 'description', content: 'アイゼンハワーマトリクス（緊急度×重要度）に基づくモダンな4象限タスク管理アプリケーション' }
  ]
});

const {
  tasks,
  tasksByQuadrant,
  isLoading,
  error,
  fetchTasks,
  createTask,
  updateTask,
  toggleComplete,
  changeQuadrant,
  deleteTask,
} = useTasks();

// --- 日時ヘルパー (JST 日本時間対応) ---
// input type="datetime-local" (YYYY-MM-DDTHH:mm) を JST 文字列 (YYYY-MM-DD HH:mm:ss) に変換
const toJstFormattedString = (datetimeLocalVal: string): string | null => {
  if (!datetimeLocalVal) return null;
  // 形式: "2026-10-01T15:30" -> "2026-10-01 15:30:00"
  return datetimeLocalVal.replace('T', ' ') + (datetimeLocalVal.length === 16 ? ':00' : '');
};

// API から受け取った ISO8601 / 日時文字列を input type="datetime-local" 用 (YYYY-MM-DDTHH:mm) に変換
const toDatetimeLocalValue = (dateStr: string | null): string => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '';

  // JST の年・月・日・時・分を取得
  const pad = (n: number) => n.toString().padStart(2, '0');
  
  // Intl API で Asia/Tokyo 時刻を取得
  const formatter = new Intl.DateTimeFormat('ja-JP', {
    timeZone: 'Asia/Tokyo',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  });
  
  const parts = formatter.formatToParts(d);
  const getPart = (type: string) => parts.find((p) => p.type === type)?.value || '';

  const year = getPart('year');
  const month = getPart('month');
  const day = getPart('day');
  const hour = getPart('hour');
  const minute = getPart('minute');

  return `${year}-${month}-${day}T${hour}:${minute}`;
};

// 一覧表示用 (例: 10/1(木) 15:30)
const formatDate = (dateStr: string | null): string => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '';

  return d.toLocaleString('ja-JP', {
    timeZone: 'Asia/Tokyo',
    month: 'numeric',
    day: 'numeric',
    weekday: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// --- 新規登録モーダル ---
const isCreateModalOpen = ref(false);
const createTaskForm = ref<{
  title: string;
  description: string;
  priority_type: PriorityType;
  due_date: string;
}>({
  title: '',
  description: '',
  priority_type: 'urgent_important',
  due_date: '',
});

const openCreateModal = (priority: PriorityType = 'urgent_important') => {
  createTaskForm.value = {
    title: '',
    description: '',
    priority_type: priority,
    due_date: '',
  };
  isCreateModalOpen.value = true;
};

const submitCreateTask = async () => {
  if (!createTaskForm.value.title) return;

  const created = await createTask({
    title: createTaskForm.value.title,
    description: createTaskForm.value.description || null,
    priority_type: createTaskForm.value.priority_type,
    due_date: toJstFormattedString(createTaskForm.value.due_date),
  });

  if (created) {
    isCreateModalOpen.value = false;
  }
};

// --- 内容編集モーダル ---
const isEditModalOpen = ref(false);
const editingTaskId = ref<number | null>(null);
const editTaskForm = ref<{
  title: string;
  description: string;
  priority_type: PriorityType;
  due_date: string;
}>({
  title: '',
  description: '',
  priority_type: 'urgent_important',
  due_date: '',
});

const openEditModal = (task: Task) => {
  editingTaskId.value = task.id;
  editTaskForm.value = {
    title: task.title,
    description: task.description || '',
    priority_type: task.priority_type,
    due_date: toDatetimeLocalValue(task.due_date),
  };
  isEditModalOpen.value = true;
};

const submitUpdateTask = async () => {
  if (!editingTaskId.value || !editTaskForm.value.title) return;

  const updated = await updateTask(editingTaskId.value, {
    title: editTaskForm.value.title,
    description: editTaskForm.value.description || null,
    priority_type: editTaskForm.value.priority_type,
    due_date: toJstFormattedString(editTaskForm.value.due_date),
  });

  if (updated) {
    isEditModalOpen.value = false;
  }
};

const deleteCurrentEditingTask = async () => {
  if (!editingTaskId.value) return;
  const success = await deleteTask(editingTaskId.value);
  if (success) {
    isEditModalOpen.value = false;
  }
};

const handleQuadrantChange = (task: Task, event: Event) => {
  const target = event.target as HTMLSelectElement;
  changeQuadrant(task, target.value as PriorityType);
};

onMounted(() => {
  fetchTasks();
});
</script>
