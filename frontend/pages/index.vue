<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
    <!-- Header -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-30 shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-rose-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 font-black text-white text-lg">
            4Q
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-bold tracking-tight text-white">
                Team & Personal Task Matrix
              </h1>
              <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-medium">
                {{ currentView === 'team' ? (selectedMethodology === 'agile' ? 'アジャイル開発' : 'ウォーターフォール') : '個人4象限マトリクス' }}
              </span>
            </div>
            <p class="text-xs text-slate-400 hidden sm:block">チームのプロジェクトタスクから個人の4象限実行へシームレスに連携</p>
          </div>
        </div>

        <!-- Right action: refresh and create -->
        <div class="flex items-center gap-3">
          <button
            @click="fetchTasks()"
            :disabled="isLoading"
            class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition duration-150 disabled:opacity-50"
            title="最新状態に更新"
          >
            <svg class="w-5 h-5" :class="{ 'animate-spin': isLoading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
          <button
            @click="openCreateModal()"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-indigo-500 to-rose-500 text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-600 hover:to-rose-600 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">新規タスク追加</span>
            <span class="sm:hidden">追加</span>
          </button>
        </div>
      </div>

      <!-- View & Methodology Switcher Bar -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-3 py-2.5 bg-slate-900/60">
        <!-- Main Scope Tabs -->
        <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800">
          <button
            @click="currentView = 'team'"
            class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition"
            :class="currentView === 'team' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            チームタスク (Team)
            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">{{ teamTasks.length }}</span>
          </button>

          <button
            @click="currentView = 'personal'"
            class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition"
            :class="currentView === 'personal' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            個人タスク (4象限マトリクス)
            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">{{ personalTasks.length }}</span>
          </button>
        </div>

        <!-- Methodology Sub-Switcher (Only visible in Team View) -->
        <div v-if="currentView === 'team'" class="flex items-center gap-2">
          <span class="text-xs text-slate-400 font-medium">開発手法:</span>
          <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800">
            <button
              @click="selectedMethodology = 'agile'"
              class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition"
              :class="selectedMethodology === 'agile' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 hover:text-slate-200'"
            >
              <span>🏃‍♂️ アジャイル (スクラム/カンバン)</span>
            </button>
            <button
              @click="selectedMethodology = 'waterfall'"
              class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition"
              :class="selectedMethodology === 'waterfall' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40' : 'text-slate-400 hover:text-slate-200'"
            >
              <span>📊 ウォーターフォール (工程WBS)</span>
            </button>
          </div>
        </div>

        <!-- Workflow Hint Badge -->
        <div v-if="currentView === 'team'" class="text-xs text-slate-400 flex items-center gap-1 bg-slate-800/40 px-2.5 py-1 rounded-lg border border-slate-800">
          <span class="text-indigo-400 font-bold">FLOW:</span>
          チームタスクの「⚡ 取り込む」で、個人の4象限マトリクスへ展開できます
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

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 flex flex-col">
      <!-- ========================================== -->
      <!-- VIEW 1: TEAM VIEW (AGILE / WATERFALL)     -->
      <!-- ========================================== -->
      <section v-if="currentView === 'team'" class="flex-1 flex flex-col space-y-6">
        <!-- 1-A. AGILE KANBAN BOARD -->
        <div v-if="selectedMethodology === 'agile'" class="space-y-4">
          <!-- Agile Sprint Summary -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                  アジャイル スプリント・カンバン
                  <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-amber-300 border border-amber-500/20">スプリント 1</span>
                </h2>
                <p class="text-xs text-slate-400">ユーザーストーリーとタスクを進捗ステータスで管理し、個人が自律的に4象限へ取り込みます</p>
              </div>
            </div>

            <div class="flex items-center gap-4 text-xs">
              <div class="bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
                <span class="text-slate-400">総タスク数:</span>
                <span class="ml-1 font-bold text-white font-mono">{{ agileTasks.length }}</span>
              </div>
              <div class="bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
                <span class="text-slate-400">合計ポイント:</span>
                <span class="ml-1 font-bold text-amber-400 font-mono">{{ totalStoryPoints }} pt</span>
              </div>
              <button
                @click="openCreateModal({ task_scope: 'team', methodology: 'agile' })"
                class="px-3 py-1.5 rounded-lg bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/40 font-semibold transition"
              >
                + スプリントタスク追加
              </button>
            </div>
          </div>

          <!-- 4 Kanban Columns: ToDo / In Progress / Review / Done -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Column: TODO -->
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 flex flex-col min-h-[500px]">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                <h3 class="font-bold text-slate-200 text-sm flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                  未着手 (To Do)
                </h3>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-slate-800 text-slate-400">{{ agileKanbanColumns.todo.length }}</span>
              </div>
              <div class="flex-1 space-y-3 overflow-y-auto">
                <div
                  v-for="task in agileKanbanColumns.todo"
                  :key="task.id"
                  class="p-3.5 bg-slate-800/90 rounded-xl border border-slate-700/60 shadow hover:border-slate-600 transition group space-y-2.5"
                >
                  <div class="flex items-start justify-between gap-2">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-700/60 text-slate-300">
                      {{ task.agile_sprint || 'Sprint 1' }}
                    </span>
                    <span v-if="task.agile_story_points" class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
                      {{ task.agile_story_points }} pt
                    </span>
                  </div>
                  <h4 class="text-sm font-bold text-white group-hover:text-amber-200 transition cursor-pointer" @click="openEditModal(task)">
                    {{ task.title }}
                  </h4>
                  <p v-if="task.description" class="text-xs text-slate-400 line-clamp-2">{{ task.description }}</p>
                  
                  <div class="pt-2 border-t border-slate-700/50 flex items-center justify-between text-xs">
                    <span class="text-slate-400 flex items-center gap-1">
                      👤 {{ task.assigned_to || '未割当' }}
                    </span>
                    <div class="flex items-center gap-1">
                      <button
                        @click="openBranchModal(task)"
                        class="px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition font-semibold text-[11px] flex items-center gap-1"
                        title="このタスクを個人の4象限マトリクスに取り込む"
                      >
                        ⚡ 取り込む
                      </button>
                      <button
                        @click="updateStatus(task, 'in_progress')"
                        class="px-2 py-1 rounded bg-slate-700 text-slate-200 hover:bg-slate-600 transition text-[11px]"
                      >
                        進行へ →
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Column: IN PROGRESS -->
            <div class="bg-slate-900/70 border border-indigo-900/40 rounded-2xl p-4 flex flex-col min-h-[500px]">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-indigo-900/40">
                <h3 class="font-bold text-indigo-300 text-sm flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                  進行中 (In Progress)
                </h3>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-indigo-950 text-indigo-300">{{ agileKanbanColumns.in_progress.length }}</span>
              </div>
              <div class="flex-1 space-y-3 overflow-y-auto">
                <div
                  v-for="task in agileKanbanColumns.in_progress"
                  :key="task.id"
                  class="p-3.5 bg-slate-800/90 rounded-xl border border-indigo-500/30 shadow hover:border-indigo-500/50 transition group space-y-2.5"
                >
                  <div class="flex items-start justify-between gap-2">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                      {{ task.agile_sprint || 'Sprint 1' }}
                    </span>
                    <span v-if="task.agile_story_points" class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
                      {{ task.agile_story_points }} pt
                    </span>
                  </div>
                  <h4 class="text-sm font-bold text-white group-hover:text-indigo-200 transition cursor-pointer" @click="openEditModal(task)">
                    {{ task.title }}
                  </h4>
                  <p v-if="task.description" class="text-xs text-slate-400 line-clamp-2">{{ task.description }}</p>

                  <div class="pt-2 border-t border-slate-700/50 flex items-center justify-between text-xs">
                    <span class="text-indigo-300 font-medium flex items-center gap-1">
                      👤 {{ task.assigned_to || '未割当' }}
                    </span>
                    <div class="flex items-center gap-1">
                      <button
                        @click="openBranchModal(task)"
                        class="px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition font-semibold text-[11px] flex items-center gap-1"
                        title="このタスクを個人の4象限マトリクスに取り込む"
                      >
                        ⚡ 取り込む
                      </button>
                      <button
                        @click="updateStatus(task, 'review')"
                        class="px-2 py-1 rounded bg-slate-700 text-slate-200 hover:bg-slate-600 transition text-[11px]"
                      >
                        レビュー →
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Column: REVIEW -->
            <div class="bg-slate-900/70 border border-purple-900/40 rounded-2xl p-4 flex flex-col min-h-[500px]">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-purple-900/40">
                <h3 class="font-bold text-purple-300 text-sm flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                  レビュー中 (Review)
                </h3>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-purple-950 text-purple-300">{{ agileKanbanColumns.review.length }}</span>
              </div>
              <div class="flex-1 space-y-3 overflow-y-auto">
                <div
                  v-for="task in agileKanbanColumns.review"
                  :key="task.id"
                  class="p-3.5 bg-slate-800/90 rounded-xl border border-purple-500/30 shadow hover:border-purple-500/50 transition group space-y-2.5"
                >
                  <div class="flex items-start justify-between gap-2">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20">
                      {{ task.agile_sprint || 'Sprint 1' }}
                    </span>
                    <span v-if="task.agile_story_points" class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
                      {{ task.agile_story_points }} pt
                    </span>
                  </div>
                  <h4 class="text-sm font-bold text-white group-hover:text-purple-200 transition cursor-pointer" @click="openEditModal(task)">
                    {{ task.title }}
                  </h4>
                  <div class="pt-2 border-t border-slate-700/50 flex items-center justify-between text-xs">
                    <span class="text-purple-300 font-medium">👤 {{ task.assigned_to || '未割当' }}</span>
                    <button
                      @click="updateStatus(task, 'done')"
                      class="px-2 py-1 rounded bg-emerald-600/30 text-emerald-300 hover:bg-emerald-600 hover:text-white transition text-[11px] font-semibold"
                    >
                      完了 ✓
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Column: DONE -->
            <div class="bg-slate-900/70 border border-emerald-900/40 rounded-2xl p-4 flex flex-col min-h-[500px]">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-emerald-900/40">
                <h3 class="font-bold text-emerald-300 text-sm flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                  完了 (Done)
                </h3>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300">{{ agileKanbanColumns.done.length }}</span>
              </div>
              <div class="flex-1 space-y-3 overflow-y-auto">
                <div
                  v-for="task in agileKanbanColumns.done"
                  :key="task.id"
                  class="p-3.5 bg-slate-800/60 rounded-xl border border-emerald-500/20 shadow opacity-80 hover:opacity-100 transition space-y-2"
                >
                  <h4 class="text-sm font-bold text-slate-300 line-through cursor-pointer" @click="openEditModal(task)">
                    {{ task.title }}
                  </h4>
                  <div class="flex items-center justify-between text-xs text-slate-400">
                    <span>👤 {{ task.assigned_to || '未割当' }}</span>
                    <button
                      @click="updateStatus(task, 'todo')"
                      class="text-[11px] text-slate-500 hover:text-slate-300 underline"
                    >
                      ToDoに戻す
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 1-B. WATERFALL WBS VIEW -->
        <div v-else-if="selectedMethodology === 'waterfall'" class="space-y-4">
          <!-- Waterfall Summary -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                  ウォーターフォール工程管理 (WBS)
                  <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-cyan-300 border border-cyan-500/20">フェーズ進捗</span>
                </h2>
                <p class="text-xs text-slate-400">要件定義からリリースまでの各フェーズで進捗率(%)を管理し、担当者が個人タスクへ展開します</p>
              </div>
            </div>

            <button
              @click="openCreateModal({ task_scope: 'team', methodology: 'waterfall' })"
              class="px-3 py-1.5 rounded-lg bg-cyan-500/20 text-cyan-300 hover:bg-cyan-500/30 border border-cyan-500/40 font-semibold text-xs transition"
            >
              + 工程タスク追加
            </button>
          </div>

          <!-- Phase-by-Phase List -->
          <div class="space-y-4">
            <div
              v-for="phase in waterfallPhaseConfig"
              :key="phase.id"
              class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg overflow-hidden"
            >
              <div class="flex flex-wrap items-center justify-between pb-3 border-b border-slate-800 gap-2">
                <div class="flex items-center gap-2.5">
                  <span class="w-3 h-3 rounded-full" :class="phase.badgeBg"></span>
                  <h3 class="font-bold text-sm text-white">{{ phase.label }}</h3>
                  <span class="text-xs text-slate-400">({{ phase.desc }})</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                  <span class="text-slate-400 font-mono">{{ waterfallPhases[phase.id]?.length || 0 }} タスク</span>
                </div>
              </div>

              <!-- Phase Tasks Table / Rows -->
              <div class="mt-3 space-y-2">
                <template v-if="waterfallPhases[phase.id] && waterfallPhases[phase.id].length > 0">
                  <div
                    v-for="task in waterfallPhases[phase.id]"
                    :key="task.id"
                    class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60 flex flex-wrap items-center justify-between gap-3 hover:border-cyan-500/40 transition"
                  >
                    <div class="flex-1 min-w-[200px]">
                      <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-700 text-slate-300">
                          👤 {{ task.assigned_to || '未定' }}
                        </span>
                        <h4 class="text-sm font-bold text-white hover:text-cyan-200 transition cursor-pointer" @click="openEditModal(task)">
                          {{ task.title }}
                        </h4>
                      </div>
                      <p v-if="task.description" class="text-xs text-slate-400 mt-1 line-clamp-1">{{ task.description }}</p>
                    </div>

                    <!-- Progress bar -->
                    <div class="w-40 flex items-center gap-2">
                      <div class="flex-1 bg-slate-700 rounded-full h-2 overflow-hidden">
                        <div
                          class="h-full bg-gradient-to-r from-cyan-500 to-indigo-500 rounded-full transition-all duration-300"
                          :style="{ width: `${task.progress_rate || 0}%` }"
                        ></div>
                      </div>
                      <span class="text-xs font-mono font-bold text-cyan-300 w-9 text-right">{{ task.progress_rate || 0 }}%</span>
                    </div>

                    <!-- Date & Actions -->
                    <div class="flex items-center gap-3 text-xs">
                      <span v-if="task.due_date" class="text-slate-400 flex items-center gap-1 font-mono">
                        📅 {{ formatDate(task.due_date) }}
                      </span>
                      <button
                        @click="openBranchModal(task)"
                        class="px-2.5 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition font-semibold text-xs flex items-center gap-1"
                        title="個人の4象限マトリクスに取り込む"
                      >
                        ⚡ 4象限へ
                      </button>
                      <button
                        @click="openEditModal(task)"
                        class="p-1 rounded text-slate-400 hover:text-white hover:bg-slate-700 transition"
                      >
                        ✏️
                      </button>
                    </div>
                  </div>
                </template>
                <div v-else class="py-4 text-center text-xs text-slate-500 border border-dashed border-slate-800 rounded-xl">
                  このフェーズのタスクはまだありません
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ========================================== -->
      <!-- VIEW 2: PERSONAL 4-QUADRANT MATRIX VIEW   -->
      <!-- ========================================== -->
      <section v-else-if="currentView === 'personal'" class="flex-1 flex flex-col space-y-4">
        <!-- Matrix Description Banner -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <div>
              <h2 class="text-base font-bold text-white flex items-center gap-2">
                個人の4象限マトリクス (My Focus)
                <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-indigo-300 border border-indigo-500/20">緊急度 × 重要度</span>
              </h2>
              <p class="text-xs text-slate-400">チームから取り込んだタスクや個人のタスクを緊急度・重要度で優先順位付けして実行します</p>
            </div>
          </div>

          <div class="text-xs text-slate-400 bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
            チームタスク由来: <span class="font-bold text-indigo-400">{{ personalTasks.filter(t => t.team_task_id).length }}</span> 件
          </div>
        </div>

        <!-- 4 Quadrants Matrix Grid (2x2) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1">
          <!-- Quadrant 1: 緊急 × 重要 (DO / 赤) -->
          <div class="flex flex-col rounded-2xl border border-rose-500/30 bg-gradient-to-b from-rose-950/20 to-slate-900/60 shadow-xl overflow-hidden backdrop-blur">
            <div class="px-5 py-3.5 border-b border-rose-500/20 bg-rose-900/20 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-rose-500 animate-pulse"></span>
                <div>
                  <h3 class="font-bold text-rose-100 flex items-center gap-2 text-sm">
                    第1象限：緊急 × 重要
                    <span class="text-xs px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30">すぐやる (DO)</span>
                  </h3>
                  <p class="text-[11px] text-rose-300/70">危機、締切直前、最優先の課題</p>
                </div>
              </div>
              <button
                @click="openCreateModal({ task_scope: 'personal', priority_type: 'urgent_important' })"
                class="p-1.5 rounded-md hover:bg-rose-500/20 text-rose-300 hover:text-white transition"
                title="タスク追加"
              >
                +
              </button>
            </div>

            <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
              <template v-if="tasksByQuadrant.urgent_important.length > 0">
                <div
                  v-for="task in tasksByQuadrant.urgent_important"
                  :key="task.id"
                  class="group p-3.5 rounded-xl border border-rose-500/20 bg-slate-800/80 hover:bg-slate-800 hover:border-rose-500/40 transition shadow space-y-2"
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
                        <div v-if="task.team_task_title" class="mb-1">
                          <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center gap-1 w-fit">
                            🏢 チーム由来: {{ task.team_task_title }}
                          </span>
                        </div>
                        <h4
                          class="text-sm font-semibold text-slate-100 transition truncate group-hover:text-rose-200"
                          :class="{ 'line-through text-slate-400': task.is_completed }"
                        >
                          {{ task.title }}
                        </h4>
                        <p v-if="task.description" class="text-xs text-slate-400 mt-1 whitespace-pre-wrap line-clamp-2">
                          {{ task.description }}
                        </p>
                        <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                          <span v-if="task.due_date" class="flex items-center gap-1 text-rose-300 font-medium">
                            📅 {{ formatDate(task.due_date) }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Actions -->
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
                      <button @click="openEditModal(task)" class="p-1 text-slate-400 hover:text-white">✏️</button>
                      <button @click="deleteTask(task.id)" class="p-1 text-slate-500 hover:text-rose-400">🗑️</button>
                    </div>
                  </div>
                </div>
              </template>
              <div v-else class="h-32 flex flex-col items-center justify-center text-center text-slate-500 text-xs border border-dashed border-rose-500/20 rounded-xl">
                <p>緊急かつ重要なタスクはありません</p>
              </div>
            </div>
          </div>

          <!-- Quadrant 2: 非緊急 × 重要 (PLAN / 青) -->
          <div class="flex flex-col rounded-2xl border border-sky-500/30 bg-gradient-to-b from-sky-950/20 to-slate-900/60 shadow-xl overflow-hidden backdrop-blur">
            <div class="px-5 py-3.5 border-b border-sky-500/20 bg-sky-900/20 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-sky-500"></span>
                <div>
                  <h3 class="font-bold text-sky-100 flex items-center gap-2 text-sm">
                    第2象限：非緊急 × 重要
                    <span class="text-xs px-2 py-0.5 rounded bg-sky-500/20 text-sky-300 border border-sky-500/30">計画する (PLAN)</span>
                  </h3>
                  <p class="text-[11px] text-sky-300/70">中長期計画、自己研鑽、予防策、価値創出</p>
                </div>
              </div>
              <button
                @click="openCreateModal({ task_scope: 'personal', priority_type: 'not_urgent_important' })"
                class="p-1.5 rounded-md hover:bg-sky-500/20 text-sky-300 hover:text-white transition"
              >
                +
              </button>
            </div>

            <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
              <template v-if="tasksByQuadrant.not_urgent_important.length > 0">
                <div
                  v-for="task in tasksByQuadrant.not_urgent_important"
                  :key="task.id"
                  class="group p-3.5 rounded-xl border border-sky-500/20 bg-slate-800/80 hover:bg-slate-800 hover:border-sky-500/40 transition shadow space-y-2"
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
                        <div v-if="task.team_task_title" class="mb-1">
                          <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center gap-1 w-fit">
                            🏢 チーム由来: {{ task.team_task_title }}
                          </span>
                        </div>
                        <h4
                          class="text-sm font-semibold text-slate-100 transition truncate group-hover:text-sky-200"
                          :class="{ 'line-through text-slate-400': task.is_completed }"
                        >
                          {{ task.title }}
                        </h4>
                        <p v-if="task.description" class="text-xs text-slate-400 mt-1 whitespace-pre-wrap line-clamp-2">
                          {{ task.description }}
                        </p>
                        <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                          <span v-if="task.due_date" class="flex items-center gap-1 text-sky-300 font-medium">
                            📅 {{ formatDate(task.due_date) }}
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
                      <button @click="openEditModal(task)" class="p-1 text-slate-400 hover:text-white">✏️</button>
                      <button @click="deleteTask(task.id)" class="p-1 text-slate-500 hover:text-rose-400">🗑️</button>
                    </div>
                  </div>
                </div>
              </template>
              <div v-else class="h-32 flex flex-col items-center justify-center text-center text-slate-500 text-xs border border-dashed border-sky-500/20 rounded-xl">
                <p>第2象限（計画）のタスクを追加しましょう</p>
              </div>
            </div>
          </div>

          <!-- Quadrant 3: 緊急 × 非重要 (DELEGATE / 黄) -->
          <div class="flex flex-col rounded-2xl border border-amber-500/30 bg-gradient-to-b from-amber-950/20 to-slate-900/60 shadow-xl overflow-hidden backdrop-blur">
            <div class="px-5 py-3.5 border-b border-amber-500/20 bg-amber-900/20 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                <div>
                  <h3 class="font-bold text-amber-100 flex items-center gap-2 text-sm">
                    第3象限：緊急 × 非重要
                    <span class="text-xs px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">委託する (DELEGATE)</span>
                  </h3>
                  <p class="text-[11px] text-amber-300/70">割り込み、低優先な会議や連絡</p>
                </div>
              </div>
              <button
                @click="openCreateModal({ task_scope: 'personal', priority_type: 'urgent_not_important' })"
                class="p-1.5 rounded-md hover:bg-amber-500/20 text-amber-300 hover:text-white transition"
              >
                +
              </button>
            </div>

            <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
              <template v-if="tasksByQuadrant.urgent_not_important.length > 0">
                <div
                  v-for="task in tasksByQuadrant.urgent_not_important"
                  :key="task.id"
                  class="group p-3.5 rounded-xl border border-amber-500/20 bg-slate-800/80 hover:bg-slate-800 hover:border-amber-500/40 transition shadow space-y-2"
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
                        <div v-if="task.team_task_title" class="mb-1">
                          <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center gap-1 w-fit">
                            🏢 チーム由来: {{ task.team_task_title }}
                          </span>
                        </div>
                        <h4
                          class="text-sm font-semibold text-slate-100 transition truncate group-hover:text-amber-200"
                          :class="{ 'line-through text-slate-400': task.is_completed }"
                        >
                          {{ task.title }}
                        </h4>
                        <p v-if="task.description" class="text-xs text-slate-400 mt-1 whitespace-pre-wrap line-clamp-2">
                          {{ task.description }}
                        </p>
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
                      <button @click="openEditModal(task)" class="p-1 text-slate-400 hover:text-white">✏️</button>
                      <button @click="deleteTask(task.id)" class="p-1 text-slate-500 hover:text-rose-400">🗑️</button>
                    </div>
                  </div>
                </div>
              </template>
              <div v-else class="h-32 flex flex-col items-center justify-center text-center text-slate-500 text-xs border border-dashed border-amber-500/20 rounded-xl">
                <p>委託対象タスクはありません</p>
              </div>
            </div>
          </div>

          <!-- Quadrant 4: 非緊急 × 非重要 (ELIMINATE / 灰) -->
          <div class="flex flex-col rounded-2xl border border-slate-700 bg-gradient-to-b from-slate-800/40 to-slate-900/60 shadow-xl overflow-hidden backdrop-blur">
            <div class="px-5 py-3.5 border-b border-slate-700 bg-slate-800/40 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                <div>
                  <h3 class="font-bold text-slate-200 flex items-center gap-2 text-sm">
                    第4象限：非緊急 × 非重要
                    <span class="text-xs px-2 py-0.5 rounded bg-slate-700 text-slate-300 border border-slate-600">削減する (ELIMINATE)</span>
                  </h3>
                  <p class="text-[11px] text-slate-400">浪費時間、不要な作業の削減</p>
                </div>
              </div>
              <button
                @click="openCreateModal({ task_scope: 'personal', priority_type: 'not_urgent_not_important' })"
                class="p-1.5 rounded-md hover:bg-slate-700 text-slate-300 hover:text-white transition"
              >
                +
              </button>
            </div>

            <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
              <template v-if="tasksByQuadrant.not_urgent_not_important.length > 0">
                <div
                  v-for="task in tasksByQuadrant.not_urgent_not_important"
                  :key="task.id"
                  class="group p-3.5 rounded-xl border border-slate-700/60 bg-slate-800/60 hover:bg-slate-800 hover:border-slate-600 transition shadow space-y-2"
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
                        <div v-if="task.team_task_title" class="mb-1">
                          <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center gap-1 w-fit">
                            🏢 チーム由来: {{ task.team_task_title }}
                          </span>
                        </div>
                        <h4
                          class="text-sm font-semibold text-slate-300 transition truncate group-hover:text-white"
                          :class="{ 'line-through text-slate-500': task.is_completed }"
                        >
                          {{ task.title }}
                        </h4>
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
                      <button @click="openEditModal(task)" class="p-1 text-slate-400 hover:text-white">✏️</button>
                      <button @click="deleteTask(task.id)" class="p-1 text-slate-500 hover:text-rose-400">🗑️</button>
                    </div>
                  </div>
                </div>
              </template>
              <div v-else class="h-32 flex flex-col items-center justify-center text-center text-slate-500 text-xs border border-dashed border-slate-700 rounded-xl">
                <p>無駄なタスクはありません</p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- ========================================== -->
    <!-- MODAL 1: BRANCH TO PERSONAL MODAL          -->
    <!-- (チームタスクを個人の4象限に取り込むモーダル) -->
    <!-- ========================================== -->
    <div
      v-if="isBranchModalOpen && targetTeamTask"
      class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 border-b border-slate-800 bg-gradient-to-r from-indigo-950/60 to-slate-900 flex justify-between items-center">
          <div class="flex items-center gap-2.5">
            <span class="p-1.5 rounded-lg bg-indigo-500/20 text-indigo-400">⚡</span>
            <div>
              <h3 class="font-bold text-white text-sm">個人の4象限マトリクスへ取り込む</h3>
              <p class="text-[11px] text-slate-400">チームタスクを自分の実行タスクとして展開します</p>
            </div>
          </div>
          <button @click="isBranchModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitBranchToPersonal" class="p-6 space-y-4">
          <!-- Team task summary -->
          <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 text-xs space-y-1">
            <span class="text-slate-400">元のチームタスク:</span>
            <div class="font-bold text-white text-sm">{{ targetTeamTask.title }}</div>
            <div v-if="targetTeamTask.agile_sprint" class="text-amber-400 font-mono text-[11px]">
              スプリント: {{ targetTeamTask.agile_sprint }} ({{ targetTeamTask.agile_story_points || 0 }} pt)
            </div>
            <div v-else-if="targetTeamTask.waterfall_phase" class="text-cyan-400 text-[11px]">
              工程: {{ getPhaseLabel(targetTeamTask.waterfall_phase) }}
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">配置する4象限 (重要度 × 緊急度) *</label>
            <div class="grid grid-cols-2 gap-2">
              <label
                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                :class="branchForm.priority_type === 'urgent_important' ? 'border-rose-500 bg-rose-500/20 text-rose-200' : 'border-slate-800 bg-slate-950 text-slate-400 hover:bg-slate-800'"
              >
                <input type="radio" value="urgent_important" v-model="branchForm.priority_type" class="sr-only" />
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                第1象限 (緊急・重要)
              </label>

              <label
                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                :class="branchForm.priority_type === 'not_urgent_important' ? 'border-sky-500 bg-sky-500/20 text-sky-200' : 'border-slate-800 bg-slate-950 text-slate-400 hover:bg-slate-800'"
              >
                <input type="radio" value="not_urgent_important" v-model="branchForm.priority_type" class="sr-only" />
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                第2象限 (非緊急・重要)
              </label>

              <label
                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                :class="branchForm.priority_type === 'urgent_not_important' ? 'border-amber-500 bg-amber-500/20 text-amber-200' : 'border-slate-800 bg-slate-950 text-slate-400 hover:bg-slate-800'"
              >
                <input type="radio" value="urgent_not_important" v-model="branchForm.priority_type" class="sr-only" />
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                第3象限 (緊急・非重要)
              </label>

              <label
                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                :class="branchForm.priority_type === 'not_urgent_not_important' ? 'border-slate-600 bg-slate-700/30 text-slate-300' : 'border-slate-800 bg-slate-950 text-slate-400 hover:bg-slate-800'"
              >
                <input type="radio" value="not_urgent_not_important" v-model="branchForm.priority_type" class="sr-only" />
                <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                第4象限 (非緊急・非重要)
              </label>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">個人のタスクタイトル</label>
            <input
              v-model="branchForm.title"
              type="text"
              required
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">個人の締切日時 (日本時間)</label>
            <input
              v-model="branchForm.due_date"
              type="datetime-local"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div class="pt-2 flex justify-end gap-3 border-t border-slate-800">
            <button
              type="button"
              @click="isBranchModalOpen = false"
              class="px-4 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-800 transition"
            >
              キャンセル
            </button>
            <button
              type="submit"
              :disabled="isLoading"
              class="px-5 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-indigo-500 to-rose-500 text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-600 hover:to-rose-600 transition"
            >
              4象限に取り込む
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: CREATE TASK MODAL                 -->
    <!-- ========================================== -->
    <div
      v-if="isCreateModalOpen"
      class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-800/50">
          <h3 class="font-bold text-white text-base">新規タスク追加</h3>
          <button @click="isCreateModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitCreateTask" class="p-6 space-y-4">
          <!-- Scope & Methodology Selector -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">タスク区分 *</label>
              <select
                v-model="createTaskForm.task_scope"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="team">チームタスク (Team)</option>
                <option value="personal">個人タスク (Personal)</option>
              </select>
            </div>

            <div v-if="createTaskForm.task_scope === 'team'">
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">開発手法 *</label>
              <select
                v-model="createTaskForm.methodology"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="agile">アジャイル (スクラム/カンバン)</option>
                <option value="waterfall">ウォーターフォール (WBS工程)</option>
              </select>
            </div>
            <div v-else>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">4象限優先度 *</label>
              <select
                v-model="createTaskForm.priority_type"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="urgent_important">第1象限 (緊急・重要)</option>
                <option value="not_urgent_important">第2象限 (非緊急・重要)</option>
                <option value="urgent_not_important">第3象限 (緊急・非重要)</option>
                <option value="not_urgent_not_important">第4象限 (非緊急・非重要)</option>
              </select>
            </div>
          </div>

          <!-- Agile specific fields -->
          <div v-if="createTaskForm.task_scope === 'team' && createTaskForm.methodology === 'agile'" class="grid grid-cols-2 gap-3 p-3 bg-slate-950 rounded-xl border border-slate-800">
            <div>
              <label class="block text-xs font-semibold text-amber-300 uppercase mb-1">スプリント名</label>
              <input
                v-model="createTaskForm.agile_sprint"
                placeholder="例: Sprint 1"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-amber-300 uppercase mb-1">ストーリーポイント</label>
              <input
                v-model.number="createTaskForm.agile_story_points"
                type="number"
                min="0"
                max="100"
                placeholder="例: 3"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white"
              />
            </div>
          </div>

          <!-- Waterfall specific fields -->
          <div v-if="createTaskForm.task_scope === 'team' && createTaskForm.methodology === 'waterfall'" class="grid grid-cols-2 gap-3 p-3 bg-slate-950 rounded-xl border border-slate-800">
            <div>
              <label class="block text-xs font-semibold text-cyan-300 uppercase mb-1">対象フェーズ</label>
              <select
                v-model="createTaskForm.waterfall_phase"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white"
              >
                <option value="requirement">1. 要件定義</option>
                <option value="design">2. 基本・詳細設計</option>
                <option value="development">3. 実装・開発</option>
                <option value="testing">4. テスト・検証</option>
                <option value="release">5. リリース・移行</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-cyan-300 uppercase mb-1">進捗率 (%)</label>
              <input
                v-model.number="createTaskForm.progress_rate"
                type="number"
                min="0"
                max="100"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">タイトル *</label>
            <input
              v-model="createTaskForm.title"
              type="text"
              required
              placeholder="タスクのタイトル"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">詳細メモ</label>
            <textarea
              v-model="createTaskForm.description"
              rows="3"
              placeholder="概要、URL、メモなど"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">担当者</label>
              <input
                v-model="createTaskForm.assigned_to"
                placeholder="例: 田中, 自分"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
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
              class="px-5 py-2 rounded-lg text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg transition"
            >
              登録
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 3: EDIT TASK MODAL                   -->
    <!-- ========================================== -->
    <div
      v-if="isEditModalOpen"
      class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
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
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">詳細メモ</label>
            <textarea
              v-model="editTaskForm.description"
              rows="3"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">担当者</label>
              <input
                v-model="editTaskForm.assigned_to"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">4象限優先度</label>
              <select
                v-model="editTaskForm.priority_type"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="urgent_important">第1象限 (赤)</option>
                <option value="not_urgent_important">第2象限 (青)</option>
                <option value="urgent_not_important">第3象限 (黄)</option>
                <option value="not_urgent_not_important">第4象限 (灰)</option>
              </select>
            </div>
          </div>

          <!-- If waterfall, progress rate -->
          <div v-if="editTaskForm.methodology === 'waterfall'" class="p-3 bg-slate-950 rounded-xl border border-slate-800">
            <div class="flex justify-between items-center mb-1">
              <label class="text-xs font-semibold text-cyan-300">進捗率: {{ editTaskForm.progress_rate }}%</label>
            </div>
            <input
              type="range"
              min="0"
              max="100"
              v-model.number="editTaskForm.progress_rate"
              class="w-full accent-cyan-500"
            />
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

          <div class="pt-2 flex justify-between items-center border-t border-slate-800">
            <button
              type="button"
              @click="deleteCurrentEditingTask"
              class="px-3 py-2 rounded-lg text-sm text-rose-400 hover:bg-rose-500/10 transition flex items-center gap-1.5"
            >
              削除
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
                class="px-5 py-2 rounded-lg text-sm font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg transition"
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
import { ref, computed, onMounted } from 'vue';
import { useTasks } from '~/composables/useTasks';
import type { PriorityType, TaskScope, Methodology, WaterfallPhase, Task } from '~/types/task';

useHead({
  title: 'チーム＆個人タスクマネージャー | Nuxt 3 × Laravel 11',
  meta: [
    { name: 'description', content: 'アジャイル・ウォーターフォールによるチーム開発と個人の4象限マトリクスを直結するタスクマネージャー' }
  ]
});

const {
  tasks,
  teamTasks,
  personalTasks,
  tasksByQuadrant,
  agileTasks,
  agileKanbanColumns,
  waterfallTasks,
  waterfallPhases,
  isLoading,
  error,
  fetchTasks,
  createTask,
  updateTask,
  branchToPersonal,
  toggleComplete,
  changeQuadrant,
  updateStatus,
  deleteTask,
} = useTasks();

// --- View State ---
const currentView = ref<'team' | 'personal'>('team'); // 'team' or 'personal'
const selectedMethodology = ref<Methodology>('agile'); // 'agile' or 'waterfall'

// Agile Story Points summary
const totalStoryPoints = computed(() => {
  return agileTasks.value.reduce((sum, t) => sum + (t.agile_story_points || 0), 0);
});

// Waterfall phase configurations
const waterfallPhaseConfig: { id: WaterfallPhase; label: string; desc: string; badgeBg: string }[] = [
  { id: 'requirement', label: '1. 要件定義 (Requirement)', desc: '要求分析、スコープ確定', badgeBg: 'bg-indigo-500' },
  { id: 'design', label: '2. 基本・詳細設計 (Design)', desc: 'アーキテクチャ、DB設計、UI仕様', badgeBg: 'bg-cyan-500' },
  { id: 'development', label: '3. 実装・開発 (Development)', desc: '機能コーディング、単体テスト', badgeBg: 'bg-amber-500' },
  { id: 'testing', label: '4. テスト・検証 (Testing)', desc: '結合・E2Eテスト、総合検証', badgeBg: 'bg-purple-500' },
  { id: 'release', label: '5. リリース・移行 (Release)', desc: '本番デプロイ、監視・引渡し', badgeBg: 'bg-emerald-500' },
];

const getPhaseLabel = (phase: WaterfallPhase | null) => {
  const item = waterfallPhaseConfig.find((p) => p.id === phase);
  return item ? item.label : '未定';
};

// --- 日時ヘルパー (JST 日本時間対応) ---
const toJstFormattedString = (datetimeLocalVal: string): string | null => {
  if (!datetimeLocalVal) return null;
  return datetimeLocalVal.replace('T', ' ') + (datetimeLocalVal.length === 16 ? ':00' : '');
};

const toDatetimeLocalValue = (dateStr: string | null): string => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '';

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

  return `${getPart('year')}-${getPart('month')}-${getPart('day')}T${getPart('hour')}:${getPart('minute')}`;
};

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

// --- チームタスクから個人4象限への取り込み (Branch to Personal) ---
const isBranchModalOpen = ref(false);
const targetTeamTask = ref<Task | null>(null);
const branchForm = ref<{
  priority_type: PriorityType;
  title: string;
  due_date: string;
}>({
  priority_type: 'urgent_important',
  title: '',
  due_date: '',
});

const openBranchModal = (teamTask: Task) => {
  targetTeamTask.value = teamTask;
  branchForm.value = {
    priority_type: teamTask.priority_type || 'urgent_important',
    title: teamTask.title,
    due_date: toDatetimeLocalValue(teamTask.due_date),
  };
  isBranchModalOpen.value = true;
};

const submitBranchToPersonal = async () => {
  if (!targetTeamTask.value) return;

  const result = await branchToPersonal(targetTeamTask.value.id, {
    priority_type: branchForm.value.priority_type,
    title: branchForm.value.title,
    due_date: toJstFormattedString(branchForm.value.due_date),
  });

  if (result) {
    isBranchModalOpen.value = false;
    // 取り込み完了後、個人の4象限ビューに切り替えて確認しやすくする
    currentView.value = 'personal';
  }
};

// --- 新規登録モーダル ---
const isCreateModalOpen = ref(false);
const createTaskForm = ref<{
  task_scope: TaskScope;
  methodology: Methodology;
  title: string;
  description: string;
  priority_type: PriorityType;
  assigned_to: string;
  agile_sprint: string;
  agile_story_points: number | null;
  waterfall_phase: WaterfallPhase;
  progress_rate: number;
  due_date: string;
}>({
  task_scope: 'team',
  methodology: 'agile',
  title: '',
  description: '',
  priority_type: 'urgent_important',
  assigned_to: '',
  agile_sprint: 'Sprint 1',
  agile_story_points: 3,
  waterfall_phase: 'development',
  progress_rate: 0,
  due_date: '',
});

const openCreateModal = (defaults: Partial<typeof createTaskForm.value> = {}) => {
  createTaskForm.value = {
    task_scope: defaults.task_scope || (currentView.value === 'team' ? 'team' : 'personal'),
    methodology: defaults.methodology || selectedMethodology.value,
    title: '',
    description: '',
    priority_type: defaults.priority_type || 'urgent_important',
    assigned_to: '',
    agile_sprint: 'Sprint 1',
    agile_story_points: 3,
    waterfall_phase: 'development',
    progress_rate: 0,
    due_date: '',
  };
  isCreateModalOpen.value = true;
};

const submitCreateTask = async () => {
  if (!createTaskForm.value.title) return;

  const created = await createTask({
    task_scope: createTaskForm.value.task_scope,
    methodology: createTaskForm.value.task_scope === 'team' ? createTaskForm.value.methodology : 'matrix',
    title: createTaskForm.value.title,
    description: createTaskForm.value.description || null,
    priority_type: createTaskForm.value.priority_type,
    assigned_to: createTaskForm.value.assigned_to || null,
    agile_sprint: createTaskForm.value.agile_sprint || null,
    agile_story_points: createTaskForm.value.agile_story_points,
    waterfall_phase: createTaskForm.value.waterfall_phase,
    progress_rate: createTaskForm.value.progress_rate,
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
  methodology: Methodology;
  title: string;
  description: string;
  priority_type: PriorityType;
  assigned_to: string;
  progress_rate: number;
  due_date: string;
}>({
  methodology: 'matrix',
  title: '',
  description: '',
  priority_type: 'urgent_important',
  assigned_to: '',
  progress_rate: 0,
  due_date: '',
});

const openEditModal = (task: Task) => {
  editingTaskId.value = task.id;
  editTaskForm.value = {
    methodology: task.methodology,
    title: task.title,
    description: task.description || '',
    priority_type: task.priority_type,
    assigned_to: task.assigned_to || '',
    progress_rate: task.progress_rate || 0,
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
    assigned_to: editTaskForm.value.assigned_to || null,
    progress_rate: editTaskForm.value.progress_rate,
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
