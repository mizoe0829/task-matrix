<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
    <!-- Header -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-30 shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div
            @click="navigateTo('global')"
            class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-rose-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 font-black text-white text-lg cursor-pointer hover:scale-105 transition"
            title="全体ダッシュボードへ戻る"
          >
            4Q
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-bold tracking-tight text-white flex items-center gap-2">
                Task Matrix
                <span class="text-slate-600">/</span>
                <span v-if="currentProject" class="text-indigo-300 font-semibold truncate max-w-[200px] sm:max-w-xs">
                  {{ currentProject.name }}
                </span>
                <span v-else class="text-slate-300 font-medium">全社プロジェクト</span>
              </h1>
              <span
                v-if="currentProject"
                class="text-[10px] px-2 py-0.5 rounded-full font-medium border"
                :class="currentProject.methodology === 'agile' ? 'bg-amber-500/10 text-amber-300 border-amber-500/30' : 'bg-cyan-500/10 text-cyan-300 border-cyan-500/30'"
              >
                {{ currentProject.methodology === 'agile' ? '🏃‍♂️ アジャイル' : '📊 ウォーターフォール' }}
              </span>
            </div>
            <p class="text-xs text-slate-400 hidden sm:block">全体ダッシュボード ➔ プロジェクト管理 ➔ チームタスク ➔ 個人の4象限実行</p>
          </div>
        </div>

        <!-- Right action: refresh and create -->
        <div class="flex items-center gap-3">
          <!-- Active project selector dropdown -->
          <div class="relative">
            <select
              :value="currentProject?.id || ''"
              @change="handleProjectDropdownChange($event)"
              class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-indigo-500 max-w-[160px] truncate"
            >
              <option value="">📂 すべてのプロジェクト</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">
                {{ p.name }}
              </option>
            </select>
          </div>

          <!-- AI Coach Button -->
          <button
            @click="openCoachModal()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gradient-to-r from-purple-500/20 to-indigo-500/20 text-purple-300 border border-purple-500/40 hover:bg-purple-500/30 hover:text-white transition shadow-sm"
            title="AI生産性コーチングを開く"
          >
            <span class="animate-pulse">✨</span>
            <span>AIコーチ</span>
          </button>

          <button
            @click="refreshAll()"
            :disabled="isLoading"
            class="p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition duration-150 disabled:opacity-50"
            title="最新状態に更新"
          >
            <svg class="w-5 h-5" :class="{ 'animate-spin': isLoading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>

          <button
            v-if="currentView === 'global'"
            @click="isCreateProjectModalOpen = true"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-600 hover:to-purple-700 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>プロジェクト新規作成</span>
          </button>
          <button
            v-else
            @click="openCreateModal()"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-indigo-500 to-rose-500 text-white shadow-lg shadow-indigo-500/25 hover:from-indigo-600 hover:to-rose-600 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>タスク追加</span>
          </button>
        </div>
      </div>

      <!-- Hierarchical Breadcrumb & View Navigation Bar -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-3 py-2.5 bg-slate-900/60">
        <!-- Navigation Hierarchy Steps -->
        <nav class="flex items-center gap-1.5 text-xs">
          <!-- Step 1: Global Dashboard -->
          <button
            @click="navigateTo('global')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold transition"
            :class="currentView === 'global' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200 bg-slate-950/60 border border-slate-800'"
          >
            <span>🏢 全体ダッシュボード</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">{{ projects.length }}</span>
          </button>

          <span class="text-slate-600">➔</span>

          <!-- Step 2: Project Dashboard -->
          <button
            @click="navigateTo('project')"
            :disabled="!currentProject"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed"
            :class="currentView === 'project' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200 bg-slate-950/60 border border-slate-800'"
          >
            <span>📊 プロジェクト詳細</span>
            <span v-if="currentProject" class="text-[10px] text-indigo-300 font-mono">({{ currentProject.name }})</span>
          </button>

          <span class="text-slate-600">➔</span>

          <!-- Step 3: Team Tasks -->
          <button
            @click="navigateTo('team')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold transition"
            :class="currentView === 'team' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200 bg-slate-950/60 border border-slate-800'"
          >
            <span>👥 チームタスク</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">{{ teamTasks.length }}</span>
          </button>

          <span class="text-slate-600">➔</span>

          <!-- Step 4: Personal 4-Quadrant Matrix -->
          <button
            @click="navigateTo('personal')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold transition"
            :class="currentView === 'personal' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-slate-200 bg-slate-950/60 border border-slate-800'"
          >
            <span>👤 個人4象限マトリクス</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">{{ personalTasks.length }}</span>
          </button>
        </nav>

        <!-- Methodology Sub-Switcher (When in Team View) -->
        <div v-if="currentView === 'team'" class="flex items-center gap-2">
          <span class="text-xs text-slate-400 font-medium">開発手法:</span>
          <div class="flex items-center bg-slate-950 p-1 rounded-xl border border-slate-800">
            <button
              @click="selectedMethodology = 'agile'"
              class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition"
              :class="selectedMethodology === 'agile' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-slate-400 hover:text-slate-200'"
            >
              <span>🏃‍♂️ アジャイル (スクラム)</span>
            </button>
            <button
              @click="selectedMethodology = 'waterfall'"
              class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-medium transition"
              :class="selectedMethodology === 'waterfall' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40' : 'text-slate-400 hover:text-slate-200'"
            >
              <span>📊 ウォーターフォール (WBS)</span>
            </button>
          </div>
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
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 flex flex-col space-y-6">

      <!-- ======================================================== -->
      <!-- VIEW 1: 全体ダッシュボード (GLOBAL PROJECTS DASHBOARD)     -->
      <!-- ======================================================== -->
      <section v-if="currentView === 'global'" class="space-y-6">
        <!-- KPI Metrics Overview Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Card 1: Total Projects -->
          <div class="p-5 rounded-2xl bg-gradient-to-b from-slate-900 to-slate-900/60 border border-slate-800 shadow-xl space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">総プロジェクト数</span>
            <div class="flex items-baseline justify-between">
              <span class="text-3xl font-black text-white font-mono">{{ projectSummary?.total_projects ?? projects.length }}</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                進行中: {{ projectSummary?.active_projects ?? projects.filter(p => p.status === 'active').length }}
              </span>
            </div>
            <p class="text-[11px] text-slate-500">組織内の管理プロジェクト</p>
          </div>

          <!-- Card 2: Total Tasks -->
          <div class="p-5 rounded-2xl bg-gradient-to-b from-slate-900 to-slate-900/60 border border-slate-800 shadow-xl space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">プロジェクト総タスク</span>
            <div class="flex items-baseline justify-between">
              <span class="text-3xl font-black text-slate-200 font-mono">{{ projectSummary?.total_tasks ?? tasks.length }}</span>
              <span class="text-xs text-slate-400">完了: {{ projectSummary?.completed_tasks ?? tasks.filter(t => t.is_completed).length }}</span>
            </div>
            <p class="text-[11px] text-slate-500">チームおよび個人タスク合計</p>
          </div>

          <!-- Card 3: Overall Progress -->
          <div class="p-5 rounded-2xl bg-gradient-to-b from-slate-900 to-slate-900/60 border border-slate-800 shadow-xl space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">全体進捗率</span>
            <div class="flex items-baseline justify-between">
              <span class="text-3xl font-black text-emerald-400 font-mono">{{ projectSummary?.overall_progress_percent ?? 0 }}%</span>
              <div class="w-16 bg-slate-800 rounded-full h-2 overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full" :style="{ width: `${projectSummary?.overall_progress_percent ?? 0}%` }"></div>
              </div>
            </div>
            <p class="text-[11px] text-slate-500">全タスク消化率</p>
          </div>

          <!-- Card 4: Urgent Important Tasks -->
          <div class="p-5 rounded-2xl bg-gradient-to-b from-rose-950/20 to-slate-900/60 border border-rose-500/30 shadow-xl space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-rose-300">緊急×重要タスク</span>
            <div class="flex items-baseline justify-between">
              <span class="text-3xl font-black text-rose-400 font-mono">{{ projectSummary?.urgent_important_tasks ?? 0 }}</span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30">要対応</span>
            </div>
            <p class="text-[11px] text-rose-300/70">全社で即座に対処すべき課題</p>
          </div>
        </div>

        <!-- Projects Header & Action Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
          <div>
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
              プロジェクト一覧
              <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">{{ projects.length }} 件</span>
            </h2>
            <p class="text-xs text-slate-400">管理対象のプロジェクトを選択して、詳細ダッシュボードやチームタスクへ移動できます</p>
          </div>
          <button
            @click="isCreateProjectModalOpen = true"
            class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition flex items-center gap-1.5"
          >
            <span>+ 新規プロジェクト作成</span>
          </button>
        </div>

        <!-- Project Cards Grid -->
        <div v-if="projects.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="project in projects"
            :key="project.id"
            class="group bg-slate-900/80 border rounded-2xl p-5 shadow-xl hover:shadow-2xl transition duration-200 flex flex-col justify-between space-y-4"
            :class="currentProject?.id === project.id ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-slate-800 hover:border-slate-700'"
          >
            <div class="space-y-3">
              <!-- Card Header -->
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2.5">
                  <div
                    class="w-3.5 h-3.5 rounded-full shrink-0 shadow-sm"
                    :style="{ backgroundColor: project.color || '#6366f1' }"
                  ></div>
                  <h3
                    @click="selectAndGoToProject(project)"
                    class="text-base font-bold text-white group-hover:text-indigo-300 transition cursor-pointer truncate"
                  >
                    {{ project.name }}
                  </h3>
                </div>

                <!-- Methodology badge -->
                <span
                  class="text-[10px] px-2 py-0.5 rounded-full font-semibold border shrink-0"
                  :class="project.methodology === 'agile' ? 'bg-amber-500/10 text-amber-300 border-amber-500/30' : 'bg-cyan-500/10 text-cyan-300 border-cyan-500/30'"
                >
                  {{ project.methodology === 'agile' ? '🏃‍♂️ アジャイル' : '📊 ウォーターフォール' }}
                </span>
              </div>

              <!-- Description -->
              <p class="text-xs text-slate-400 line-clamp-2 min-h-[32px]">
                {{ project.description || 'プロジェクトの説明はありません' }}
              </p>

              <!-- Progress Bar -->
              <div class="space-y-1.5 pt-1">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-slate-400">進捗状況</span>
                  <span class="font-mono font-bold text-indigo-300">{{ project.progress_percent }}%</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                  <div
                    class="h-full bg-gradient-to-r from-indigo-500 to-emerald-400 rounded-full transition-all duration-300"
                    :style="{ width: `${project.progress_percent}%` }"
                  ></div>
                </div>
              </div>

              <!-- Task Counts & Date meta -->
              <div class="grid grid-cols-2 gap-2 pt-2 text-xs">
                <div class="bg-slate-950/80 px-2.5 py-1.5 rounded-lg border border-slate-800/80">
                  <span class="text-slate-500">タスク数:</span>
                  <span class="ml-1 font-bold text-white font-mono">{{ project.total_tasks }}</span>
                  <span class="text-[10px] text-slate-400 ml-1">({{ project.completed_tasks }}完了)</span>
                </div>
                <div class="bg-slate-950/80 px-2.5 py-1.5 rounded-lg border border-slate-800/80">
                  <span class="text-rose-400 font-medium">緊急:</span>
                  <span class="ml-1 font-bold text-rose-300 font-mono">{{ project.urgent_important_tasks ?? 0 }}</span>
                </div>
              </div>
            </div>

            <!-- Card Bottom Action Buttons -->
            <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
              <button
                @click="confirmDeleteProject(project)"
                class="p-1.5 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-slate-800 transition text-xs"
                title="プロジェクト削除"
              >
                🗑️ 削除
              </button>

              <div class="flex items-center gap-2">
                <button
                  @click="selectAndGoToProject(project)"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition flex items-center gap-1"
                >
                  <span>ダッシュボードを開く</span>
                  <span>➔</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else class="py-16 text-center border-2 border-dashed border-slate-800 rounded-3xl bg-slate-900/30 space-y-4">
          <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center mx-auto text-2xl">
            📂
          </div>
          <div class="space-y-1">
            <h3 class="text-base font-bold text-white">プロジェクトがまだありません</h3>
            <p class="text-xs text-slate-400">新しいプロジェクトを作成して、アジャイルまたはウォーターフォールで管理を始めましょう</p>
          </div>
          <button
            @click="isCreateProjectModalOpen = true"
            class="px-5 py-2.5 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg transition"
          >
            + 最初のプロジェクトを作成
          </button>
        </div>
      </section>

      <!-- ======================================================== -->
      <!-- VIEW 2: プロジェクトダッシュボード (PROJECT DASHBOARD)     -->
      <!-- ======================================================== -->
      <section v-else-if="currentView === 'project' && currentProject" class="space-y-6">
        <!-- Project Banner Header -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
              <div
                class="w-5 h-5 rounded-full shadow"
                :style="{ backgroundColor: currentProject.color || '#6366f1' }"
              ></div>
              <div>
                <div class="flex items-center gap-2.5">
                  <h2 class="text-xl font-black text-white tracking-tight">{{ currentProject.name }}</h2>
                  <span
                    class="text-xs px-2.5 py-0.5 rounded-full font-semibold border"
                    :class="currentProject.methodology === 'agile' ? 'bg-amber-500/10 text-amber-300 border-amber-500/30' : 'bg-cyan-500/10 text-cyan-300 border-cyan-500/30'"
                  >
                    {{ currentProject.methodology === 'agile' ? '🏃‍♂️ アジャイル (スクラム)' : '📊 ウォーターフォール (WBS)' }}
                  </span>
                </div>
                <p class="text-xs text-slate-300 mt-1 max-w-2xl">{{ currentProject.description || 'プロジェクト概要なし' }}</p>
              </div>
            </div>

            <!-- Quick Jump Actions -->
            <div class="flex items-center gap-3">
              <button
                @click="goToTeamTasks()"
                class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition flex items-center gap-1.5"
              >
                <span>👥 チームタスク管理へ進む</span>
                <span>➔</span>
              </button>
              <button
                @click="goToPersonalMatrix()"
                class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition flex items-center gap-1.5"
              >
                <span>👤 個人4象限へ進む</span>
              </button>
            </div>
          </div>

          <!-- Project Stats Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
            <div class="bg-slate-950/80 p-3 rounded-xl border border-slate-800">
              <span class="text-[11px] text-slate-400">進捗率</span>
              <div class="text-xl font-black text-emerald-400 font-mono">{{ currentProject.progress_percent }}%</div>
            </div>
            <div class="bg-slate-950/80 p-3 rounded-xl border border-slate-800">
              <span class="text-[11px] text-slate-400">総タスク数</span>
              <div class="text-xl font-black text-white font-mono">{{ currentProject.total_tasks }}</div>
            </div>
            <div class="bg-slate-950/80 p-3 rounded-xl border border-slate-800">
              <span class="text-[11px] text-slate-400">完了タスク</span>
              <div class="text-xl font-black text-indigo-400 font-mono">{{ currentProject.completed_tasks }}</div>
            </div>
            <div class="bg-slate-950/80 p-3 rounded-xl border border-slate-800">
              <span class="text-[11px] text-rose-400">未完了・緊急課題</span>
              <div class="text-xl font-black text-rose-400 font-mono">{{ currentProject.urgent_important_tasks ?? 0 }}</div>
            </div>
          </div>
        </div>

        <!-- Project Tasks Summary List Preview -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-white flex items-center gap-2">
                このプロジェクトのタスク概要
                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono">{{ filteredTasks.length }} 件</span>
              </h3>
              <p class="text-xs text-slate-400">チームタスクと個人タスクの進行ステータス一覧</p>
            </div>
            <button
              @click="openCreateModal({ project_id: currentProject.id })"
              class="px-3 py-1.5 rounded-lg bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition text-xs font-semibold"
            >
              + タスク追加
            </button>
          </div>

          <!-- Tasks preview list -->
          <div v-if="filteredTasks.length > 0" class="space-y-2">
            <div
              v-for="task in filteredTasks.slice(0, 8)"
              :key="task.id"
              class="p-3 bg-slate-800/80 rounded-xl border border-slate-700/60 flex items-center justify-between gap-3 hover:border-indigo-500/40 transition"
            >
              <div class="flex items-center gap-3 flex-1 min-w-0">
                <input
                  type="checkbox"
                  :checked="task.is_completed"
                  @change="toggleComplete(task)"
                  class="rounded border-slate-700 text-indigo-600 bg-slate-900 cursor-pointer"
                />
                <div class="flex-1 min-w-0 cursor-pointer" @click="openEditModal(task)">
                  <div class="flex items-center gap-2">
                    <span
                      class="text-[10px] px-1.5 py-0.5 rounded font-medium border"
                      :class="task.task_scope === 'team' ? 'bg-indigo-500/10 text-indigo-300 border-indigo-500/30' : 'bg-slate-700 text-slate-300 border-slate-600'"
                    >
                      {{ task.task_scope === 'team' ? 'チーム' : '個人' }}
                    </span>
                    <h4
                      class="text-sm font-semibold text-white truncate"
                      :class="{ 'line-through text-slate-400': task.is_completed }"
                    >
                      {{ task.title }}
                    </h4>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-3 text-xs text-slate-400">
                <span v-if="task.assigned_to" class="text-slate-300">👤 {{ task.assigned_to }}</span>
                <span v-if="task.due_date" class="font-mono">📅 {{ formatDate(task.due_date) }}</span>
                <button
                  v-if="task.task_scope === 'team'"
                  @click="openBranchModal(task)"
                  class="px-2 py-0.5 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition text-[11px]"
                >
                  ⚡ 4象限へ
                </button>
              </div>
            </div>
          </div>
          <div v-else class="py-8 text-center text-xs text-slate-500 border border-dashed border-slate-800 rounded-xl">
            このプロジェクトにはまだタスクがありません。「+ タスク追加」からタスクを登録してください。
          </div>
        </div>
      </section>

      <!-- ======================================================== -->
      <!-- VIEW 3: チームタスク (TEAM TASKS: AGILE / WATERFALL)     -->
      <!-- ======================================================== -->
      <section v-else-if="currentView === 'team'" class="space-y-6">
        <!-- 3-A. AGILE KANBAN BOARD -->
        <div v-if="selectedMethodology === 'agile'" class="space-y-4">
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
                  <span v-if="currentProject" class="text-xs px-2 py-0.5 rounded bg-indigo-950 text-indigo-300 border border-indigo-800">
                    {{ currentProject.name }}
                  </span>
                </h2>
                <p class="text-xs text-slate-400">ユーザーストーリーとタスクを進捗ステータスで管理し、個人が自律的に4象限へ取り込みます</p>
              </div>
            </div>

            <div class="flex items-center gap-4 text-xs">
              <div class="bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
                <span class="text-slate-400">スプリントタスク:</span>
                <span class="ml-1 font-bold text-white font-mono">{{ currentAgileTasks.length }}</span>
              </div>
              <div class="bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
                <span class="text-slate-400">合計ポイント:</span>
                <span class="ml-1 font-bold text-amber-400 font-mono">{{ totalStoryPoints }} pt</span>
              </div>
              <button
                @click="openCreateModal({ task_scope: 'team', methodology: 'agile', project_id: currentProject?.id })"
                class="px-3 py-1.5 rounded-lg bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/40 font-semibold transition"
              >
                + スプリントタスク追加
              </button>
            </div>
          </div>

          <!-- 4 Kanban Columns: ToDo / In Progress / Review / Done -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- ToDo -->
            <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 flex flex-col min-h-[460px]">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                <h3 class="font-bold text-slate-200 text-sm flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                  未着手 (To Do)
                </h3>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-slate-800 text-slate-400">{{ agileColumns.todo.length }}</span>
              </div>
              <div class="flex-1 space-y-3 overflow-y-auto">
                <div
                  v-for="task in agileColumns.todo"
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
                    <span class="text-slate-400">👤 {{ task.assigned_to || '未定' }}</span>
                    <div class="flex items-center gap-1">
                      <button
                        @click="openBranchModal(task)"
                        class="px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition font-semibold text-[11px]"
                      >
                        ⚡ 取り込む
                      </button>
                      <button
                        @click="updateStatus(task, 'in_progress')"
                        class="px-2 py-1 rounded bg-slate-700 text-slate-200 hover:bg-slate-600 transition text-[11px]"
                      >
                        進行 →
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- In Progress -->
            <div class="bg-slate-900/70 border border-indigo-900/40 rounded-2xl p-4 flex flex-col min-h-[460px]">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-indigo-900/40">
                <h3 class="font-bold text-indigo-300 text-sm flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                  進行中 (In Progress)
                </h3>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-indigo-950 text-indigo-300">{{ agileColumns.in_progress.length }}</span>
              </div>
              <div class="flex-1 space-y-3 overflow-y-auto">
                <div
                  v-for="task in agileColumns.in_progress"
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
                  <div class="pt-2 border-t border-slate-700/50 flex items-center justify-between text-xs">
                    <span class="text-indigo-300">👤 {{ task.assigned_to || '未定' }}</span>
                    <div class="flex items-center gap-1">
                      <button
                        @click="openBranchModal(task)"
                        class="px-2 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition font-semibold text-[11px]"
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

            <!-- Review -->
            <div class="bg-slate-900/70 border border-purple-900/40 rounded-2xl p-4 flex flex-col min-h-[460px]">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-purple-900/40">
                <h3 class="font-bold text-purple-300 text-sm flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                  レビュー中 (Review)
                </h3>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-purple-950 text-purple-300">{{ agileColumns.review.length }}</span>
              </div>
              <div class="flex-1 space-y-3 overflow-y-auto">
                <div
                  v-for="task in agileColumns.review"
                  :key="task.id"
                  class="p-3.5 bg-slate-800/90 rounded-xl border border-purple-500/30 shadow hover:border-purple-500/50 transition group space-y-2.5"
                >
                  <h4 class="text-sm font-bold text-white group-hover:text-purple-200 transition cursor-pointer" @click="openEditModal(task)">
                    {{ task.title }}
                  </h4>
                  <div class="pt-2 border-t border-slate-700/50 flex items-center justify-between text-xs">
                    <span class="text-purple-300">👤 {{ task.assigned_to || '未定' }}</span>
                    <button
                      @click="updateStatus(task, 'done')"
                      class="px-2.5 py-1 rounded bg-emerald-600/30 text-emerald-300 hover:bg-emerald-600 hover:text-white transition text-[11px] font-semibold"
                    >
                      完了 ✓
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Done -->
            <div class="bg-slate-900/70 border border-emerald-900/40 rounded-2xl p-4 flex flex-col min-h-[460px]">
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-emerald-900/40">
                <h3 class="font-bold text-emerald-300 text-sm flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                  完了 (Done)
                </h3>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300">{{ agileColumns.done.length }}</span>
              </div>
              <div class="flex-1 space-y-3 overflow-y-auto">
                <div
                  v-for="task in agileColumns.done"
                  :key="task.id"
                  class="p-3.5 bg-slate-800/60 rounded-xl border border-emerald-500/20 shadow opacity-80 hover:opacity-100 transition space-y-2"
                >
                  <h4 class="text-sm font-bold text-slate-300 line-through cursor-pointer" @click="openEditModal(task)">
                    {{ task.title }}
                  </h4>
                  <div class="flex items-center justify-between text-xs text-slate-400">
                    <span>👤 {{ task.assigned_to || '未定' }}</span>
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

        <!-- 3-B. WATERFALL WBS VIEW -->
        <div v-else-if="selectedMethodology === 'waterfall'" class="space-y-4">
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
                  <span v-if="currentProject" class="text-xs px-2 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800">
                    {{ currentProject.name }}
                  </span>
                </h2>
                <p class="text-xs text-slate-400">要件定義からリリースまでの各フェーズで進捗率(%)を管理し、担当者が個人タスクへ展開します</p>
              </div>
            </div>

            <button
              @click="openCreateModal({ task_scope: 'team', methodology: 'waterfall', project_id: currentProject?.id })"
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
                  <span class="text-slate-400 font-mono">{{ currentWaterfallPhases[phase.id]?.length || 0 }} タスク</span>
                </div>
              </div>

              <!-- Phase Tasks Rows -->
              <div class="mt-3 space-y-2">
                <template v-if="currentWaterfallPhases[phase.id] && currentWaterfallPhases[phase.id].length > 0">
                  <div
                    v-for="task in currentWaterfallPhases[phase.id]"
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

                    <!-- Actions -->
                    <div class="flex items-center gap-3 text-xs">
                      <span v-if="task.due_date" class="text-slate-400 font-mono">📅 {{ formatDate(task.due_date) }}</span>
                      <button
                        @click="openBranchModal(task)"
                        class="px-2.5 py-1 rounded bg-indigo-600/30 text-indigo-300 hover:bg-indigo-600 hover:text-white transition font-semibold text-xs"
                      >
                        ⚡ 4象限へ
                      </button>
                      <button @click="openEditModal(task)" class="p-1 text-slate-400 hover:text-white">✏️</button>
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

      <!-- ======================================================== -->
      <!-- VIEW 4: 個人4象限タスク (PERSONAL 4-QUADRANT MATRIX)       -->
      <!-- ======================================================== -->
      <section v-else-if="currentView === 'personal'" class="space-y-4">
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
                <span v-if="currentProject" class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono">
                  プロジェクト: {{ currentProject.name }}
                </span>
              </h2>
              <p class="text-xs text-slate-400">チームから取り込んだタスクや個人のタスクを緊急度・重要度で優先順位付けして実行します</p>
            </div>
          </div>

          <div class="text-xs text-slate-400 bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
            チームタスク由来: <span class="font-bold text-indigo-400">{{ currentPersonalTasks.filter(t => t.team_task_id).length }}</span> 件
          </div>
        </div>

        <!-- 4 Quadrants Grid (2x2) -->
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
                @click="openCreateModal({ task_scope: 'personal', priority_type: 'urgent_important', project_id: currentProject?.id })"
                class="p-1.5 rounded-md hover:bg-rose-500/20 text-rose-300 hover:text-white transition"
              >
                +
              </button>
            </div>

            <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
              <template v-if="currentQuadrants.urgent_important.length > 0">
                <div
                  v-for="task in currentQuadrants.urgent_important"
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
                        <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                          <span v-if="task.due_date" class="text-rose-300 font-medium">📅 {{ formatDate(task.due_date) }}</span>
                          <span v-if="task.project_name" class="text-slate-500">📁 {{ task.project_name }}</span>
                        </div>
                      </div>
                    </div>

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
                  <p class="text-[11px] text-sky-300/70">中長期計画、予防策、価値創出</p>
                </div>
              </div>
              <button
                @click="openCreateModal({ task_scope: 'personal', priority_type: 'not_urgent_important', project_id: currentProject?.id })"
                class="p-1.5 rounded-md hover:bg-sky-500/20 text-sky-300 hover:text-white transition"
              >
                +
              </button>
            </div>

            <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
              <template v-if="currentQuadrants.not_urgent_important.length > 0">
                <div
                  v-for="task in currentQuadrants.not_urgent_important"
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
                        <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                          <span v-if="task.due_date" class="text-sky-300 font-medium">📅 {{ formatDate(task.due_date) }}</span>
                          <span v-if="task.project_name" class="text-slate-500">📁 {{ task.project_name }}</span>
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
                @click="openCreateModal({ task_scope: 'personal', priority_type: 'urgent_not_important', project_id: currentProject?.id })"
                class="p-1.5 rounded-md hover:bg-amber-500/20 text-amber-300 hover:text-white transition"
              >
                +
              </button>
            </div>

            <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
              <template v-if="currentQuadrants.urgent_not_important.length > 0">
                <div
                  v-for="task in currentQuadrants.urgent_not_important"
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
                @click="openCreateModal({ task_scope: 'personal', priority_type: 'not_urgent_not_important', project_id: currentProject?.id })"
                class="p-1.5 rounded-md hover:bg-slate-700 text-slate-300 hover:text-white transition"
              >
                +
              </button>
            </div>

            <div class="flex-1 p-4 space-y-3 overflow-y-auto max-h-[500px]">
              <template v-if="currentQuadrants.not_urgent_not_important.length > 0">
                <div
                  v-for="task in currentQuadrants.not_urgent_not_important"
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

    <!-- ======================================================== -->
    <!-- MODAL A: 新規プロジェクト作成モーダル                      -->
    <!-- ======================================================== -->
    <div
      v-if="isCreateProjectModalOpen"
      class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 border-b border-slate-800 bg-gradient-to-r from-indigo-950/60 to-slate-900 flex justify-between items-center">
          <div class="flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-indigo-500/20 text-indigo-400">📂</span>
            <h3 class="font-bold text-white text-base">新規プロジェクト作成</h3>
          </div>
          <button @click="isCreateProjectModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitCreateProject" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">プロジェクト名 *</label>
            <input
              v-model="newProjectForm.name"
              type="text"
              required
              placeholder="例: ECサイトリニューアル2026"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">プロジェクト概要</label>
            <textarea
              v-model="newProjectForm.description"
              rows="3"
              placeholder="プロジェクトの目的やスコープなど"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">開発手法 *</label>
              <select
                v-model="newProjectForm.methodology"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="agile">🏃‍♂️ アジャイル (スクラム)</option>
                <option value="waterfall">📊 ウォーターフォール (WBS)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">テーマカラー</label>
              <div class="flex items-center gap-2 mt-1">
                <input
                  v-model="newProjectForm.color"
                  type="color"
                  class="w-9 h-9 rounded-lg border border-slate-700 bg-slate-800 cursor-pointer p-0.5"
                />
                <span class="text-xs text-slate-400 font-mono">{{ newProjectForm.color }}</span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">開始予定日</label>
              <input
                v-model="newProjectForm.start_date"
                type="date"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">完了目標日</label>
              <input
                v-model="newProjectForm.end_date"
                type="date"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white"
              />
            </div>
          </div>

          <div class="pt-2 flex justify-end gap-3 border-t border-slate-800">
            <button
              type="button"
              @click="isCreateProjectModalOpen = false"
              class="px-4 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-800 transition"
            >
              キャンセル
            </button>
            <button
              type="submit"
              :disabled="isLoading || !newProjectForm.name"
              class="px-5 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg transition"
            >
              作成
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL B: チームタスクから個人4象限への取り込み              -->
    <!-- ======================================================== -->
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

          <!-- AI Smart Breakdown Toggle / Trigger -->
          <div class="p-3 bg-purple-950/30 border border-purple-500/30 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-purple-300 flex items-center gap-1.5">
                <span>⚡ AIタスク自動分解</span>
                <span class="text-[10px] text-purple-400 font-normal">(Smart Breakdown)</span>
              </span>
              <button
                type="button"
                @click="runAiBreakdownForBranch"
                :disabled="isBreakingDown"
                class="px-2.5 py-1 rounded-md text-xs font-semibold bg-purple-600 hover:bg-purple-500 text-white transition disabled:opacity-50 flex items-center gap-1"
              >
                <span v-if="isBreakingDown" class="animate-spin text-[10px]">🌀</span>
                <span v-else>✨</span>
                <span>サブタスクに分解</span>
              </button>
            </div>

            <div v-if="breakdownResult" class="space-y-2 mt-2 pt-2 border-t border-purple-500/20">
              <p class="text-[11px] text-purple-200">{{ breakdownResult.breakdown_summary }}</p>
              <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                <div
                  v-for="(sub, idx) in breakdownResult.subtasks"
                  :key="idx"
                  class="p-2 rounded-lg bg-slate-900 border border-slate-800 text-xs flex items-start justify-between gap-2"
                >
                  <div>
                    <div class="font-semibold text-white">{{ sub.title }}</div>
                    <div class="text-[11px] text-slate-400">{{ sub.description }}</div>
                  </div>
                  <div class="shrink-0 text-right">
                    <span
                      class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                      :class="{
                        'bg-rose-500/20 text-rose-300 border border-rose-500/30': sub.priority_type === 'urgent_important',
                        'bg-sky-500/20 text-sky-300 border border-sky-500/30': sub.priority_type === 'not_urgent_important',
                        'bg-amber-500/20 text-amber-300 border border-amber-500/30': sub.priority_type === 'urgent_not_important',
                        'bg-slate-700 text-slate-300': sub.priority_type === 'not_urgent_not_important',
                      }"
                    >
                      {{ sub.priority_label || (sub.priority_type === 'urgent_important' ? 'DO' : (sub.priority_type === 'not_urgent_important' ? 'PLAN' : (sub.priority_type === 'urgent_not_important' ? 'DELEGATE' : 'ELIMINATE'))) }}
                    </span>
                    <div class="text-[10px] text-slate-400 mt-0.5">約{{ sub.estimated_minutes }}分</div>
                  </div>
                </div>
              </div>

              <button
                type="button"
                @click="importBreakdownSubtasks"
                class="w-full mt-2 py-2 rounded-lg text-xs font-bold bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white shadow-lg transition"
              >
                🚀 分解された {{ breakdownResult.subtasks.length }} 件のサブタスクを一括取り込み
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">配置する4象限 *</label>
            <div class="grid grid-cols-2 gap-2">
              <label
                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                :class="branchForm.priority_type === 'urgent_important' ? 'border-rose-500 bg-rose-500/20 text-rose-200' : 'border-slate-800 bg-slate-950 text-slate-400'"
              >
                <input type="radio" value="urgent_important" v-model="branchForm.priority_type" class="sr-only" />
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                第1象限 (緊急・重要)
              </label>

              <label
                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                :class="branchForm.priority_type === 'not_urgent_important' ? 'border-sky-500 bg-sky-500/20 text-sky-200' : 'border-slate-800 bg-slate-950 text-slate-400'"
              >
                <input type="radio" value="not_urgent_important" v-model="branchForm.priority_type" class="sr-only" />
                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                第2象限 (非緊急・重要)
              </label>

              <label
                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                :class="branchForm.priority_type === 'urgent_not_important' ? 'border-amber-500 bg-amber-500/20 text-amber-200' : 'border-slate-800 bg-slate-950 text-slate-400'"
              >
                <input type="radio" value="urgent_not_important" v-model="branchForm.priority_type" class="sr-only" />
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                第3象限 (緊急・非重要)
              </label>

              <label
                class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition text-xs font-semibold"
                :class="branchForm.priority_type === 'not_urgent_not_important' ? 'border-slate-600 bg-slate-700/30 text-slate-300' : 'border-slate-800 bg-slate-950 text-slate-400'"
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
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">締切日時 (日本時間)</label>
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
              class="px-5 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-indigo-500 to-rose-500 text-white shadow-lg transition"
            >
              4象限に取り込む
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL C: 新規タスク追加モーダル                           -->
    <!-- ======================================================== -->
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
          <!-- Project selection -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">所属プロジェクト</label>
            <select
              v-model="createTaskForm.project_id"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option :value="null">-- プロジェクトなし (個人スタンドアロン) --</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">
                📂 {{ p.name }}
              </option>
            </select>
          </div>

          <!-- Scope & Methodology -->
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

          <!-- Agile Fields -->
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
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white"
              />
            </div>
          </div>

          <!-- Waterfall Fields -->
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
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">タイトル *</label>
              <button
                type="button"
                @click="runAiTriageForCreate"
                :disabled="!createTaskForm.title || isTriaging"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-gradient-to-r from-purple-600/30 to-indigo-600/30 text-purple-200 border border-purple-500/40 hover:bg-purple-600/50 transition disabled:opacity-40"
              >
                <span v-if="isTriaging" class="animate-spin text-[10px]">🌀</span>
                <span v-else>✨</span>
                <span>AIトリアージ (優先度判定)</span>
              </button>
            </div>
            <input
              v-model="createTaskForm.title"
              type="text"
              required
              placeholder="タスクのタイトル (例: 本番DB接続障害の緊急対応)"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <!-- AI Triage Result Card for Create -->
          <div
            v-if="triageResult"
            class="p-3.5 rounded-xl border border-purple-500/40 bg-gradient-to-br from-purple-950/40 via-slate-900 to-indigo-950/40 text-xs space-y-2 animate-in fade-in"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="font-bold text-purple-300 flex items-center gap-1">
                  <span>🧠 AIトリアージ判定:</span>
                </span>
                <span
                  class="px-2 py-0.5 rounded font-black text-xs shadow"
                  :class="{
                    'bg-rose-500 text-white': triageResult.priority_label === 'DO',
                    'bg-sky-500 text-white': triageResult.priority_label === 'PLAN',
                    'bg-amber-500 text-slate-900': triageResult.priority_label === 'DELEGATE',
                    'bg-slate-600 text-slate-200': triageResult.priority_label === 'ELIMINATE',
                  }"
                >
                  第{{ triageResult.priority_label === 'DO' ? '1' : (triageResult.priority_label === 'PLAN' ? '2' : (triageResult.priority_label === 'DELEGATE' ? '3' : '4')) }}象限 ({{ triageResult.priority_label }})
                </span>
              </div>
              <span class="text-[11px] text-slate-400 font-mono">
                想定: 約{{ triageResult.estimated_minutes }}分
              </span>
            </div>

            <div class="flex items-center gap-4 text-[11px] text-slate-300 py-1 border-y border-slate-800/80">
              <div>緊急度: <span class="font-bold text-amber-400">{{ '★'.repeat(triageResult.urgency_score) }}{{ '☆'.repeat(5 - triageResult.urgency_score) }}</span></div>
              <div>重要度: <span class="font-bold text-indigo-400">{{ '★'.repeat(triageResult.importance_score) }}{{ '☆'.repeat(5 - triageResult.importance_score) }}</span></div>
              <span v-if="triageResult.is_mock" class="text-[10px] text-slate-500 ml-auto">(モック推論)</span>
              <span v-else class="text-[10px] text-emerald-400 ml-auto">● Gemini AI</span>
            </div>

            <p class="text-slate-300 leading-relaxed">{{ triageResult.reason }}</p>
            <div class="text-purple-200 bg-purple-900/30 p-2 rounded-lg border border-purple-500/20">
              💡 <strong>推奨アクション:</strong> {{ triageResult.action_advice }}
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">詳細メモ</label>
            <textarea
              v-model="createTaskForm.description"
              rows="3"
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

    <!-- ======================================================== -->
    <!-- MODAL D: タスク編集モーダル                               -->
    <!-- ======================================================== -->
    <div
      v-if="isEditModalOpen"
      class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-800/50">
          <h3 class="font-bold text-white text-base">タスク内容の編集</h3>
          <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitUpdateTask" class="p-6 space-y-4">
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">タイトル *</label>
              <button
                type="button"
                @click="runAiTriageForEdit"
                :disabled="!editTaskForm.title || isTriaging"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-gradient-to-r from-purple-600/30 to-indigo-600/30 text-purple-200 border border-purple-500/40 hover:bg-purple-600/50 transition disabled:opacity-40"
              >
                <span v-if="isTriaging" class="animate-spin text-[10px]">🌀</span>
                <span v-else>✨</span>
                <span>AIトリアージ (再判定)</span>
              </button>
            </div>
            <input
              v-model="editTaskForm.title"
              type="text"
              required
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <!-- AI Triage Result Card for Edit -->
          <div
            v-if="triageResult"
            class="p-3.5 rounded-xl border border-purple-500/40 bg-gradient-to-br from-purple-950/40 via-slate-900 to-indigo-950/40 text-xs space-y-2 animate-in fade-in"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="font-bold text-purple-300 flex items-center gap-1">
                  <span>🧠 AIトリアージ判定:</span>
                </span>
                <span
                  class="px-2 py-0.5 rounded font-black text-xs shadow"
                  :class="{
                    'bg-rose-500 text-white': triageResult.priority_label === 'DO',
                    'bg-sky-500 text-white': triageResult.priority_label === 'PLAN',
                    'bg-amber-500 text-slate-900': triageResult.priority_label === 'DELEGATE',
                    'bg-slate-600 text-slate-200': triageResult.priority_label === 'ELIMINATE',
                  }"
                >
                  第{{ triageResult.priority_label === 'DO' ? '1' : (triageResult.priority_label === 'PLAN' ? '2' : (triageResult.priority_label === 'DELEGATE' ? '3' : '4')) }}象限 ({{ triageResult.priority_label }})
                </span>
              </div>
              <span class="text-[11px] text-slate-400 font-mono">
                想定: 約{{ triageResult.estimated_minutes }}分
              </span>
            </div>

            <div class="flex items-center gap-4 text-[11px] text-slate-300 py-1 border-y border-slate-800/80">
              <div>緊急度: <span class="font-bold text-amber-400">{{ '★'.repeat(triageResult.urgency_score) }}{{ '☆'.repeat(5 - triageResult.urgency_score) }}</span></div>
              <div>重要度: <span class="font-bold text-indigo-400">{{ '★'.repeat(triageResult.importance_score) }}{{ '☆'.repeat(5 - triageResult.importance_score) }}</span></div>
              <span v-if="triageResult.is_mock" class="text-[10px] text-slate-500 ml-auto">(モック推論)</span>
              <span v-else class="text-[10px] text-emerald-400 ml-auto">● Gemini AI</span>
            </div>

            <p class="text-slate-300 leading-relaxed">{{ triageResult.reason }}</p>
            <div class="text-purple-200 bg-purple-900/30 p-2 rounded-lg border border-purple-500/20">
              💡 <strong>推奨アクション:</strong> {{ triageResult.action_advice }}
            </div>
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
              class="px-3 py-2 rounded-lg text-sm text-rose-400 hover:bg-rose-500/10 transition"
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

    <!-- ======================================================== -->
    <!-- MODAL E: AI 生産性コーチング モーダル                     -->
    <!-- ======================================================== -->
    <div
      v-if="isCoachModalOpen"
      class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-2xl w-full max-w-lg shadow-2xl shadow-purple-950/50 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-800 bg-gradient-to-r from-purple-950/60 to-slate-900 flex justify-between items-center">
          <div class="flex items-center gap-2">
            <span class="text-xl">🧠</span>
            <div>
              <h3 class="font-bold text-white text-base flex items-center gap-2">
                AI 生産性コーチング
                <span class="text-[10px] font-normal px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30">
                  アイゼンハワー分析
                </span>
              </h3>
              <p class="text-xs text-slate-400">タスク配分比率と第2象限（重要・非緊急）フォーカス診断</p>
            </div>
          </div>
          <button @click="isCoachModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <div class="p-6 space-y-5">
          <!-- Loading state -->
          <div v-if="isCoaching" class="py-12 text-center space-y-3">
            <div class="w-10 h-10 border-4 border-purple-500/30 border-t-purple-500 rounded-full animate-spin mx-auto"></div>
            <p class="text-xs text-purple-300">タスクの象限バランスを分析中...</p>
          </div>

          <div v-else-if="coachResult" class="space-y-4">
            <!-- Health Score Card -->
            <div class="p-4 rounded-xl bg-gradient-to-br from-purple-950/40 to-slate-950 border border-purple-500/30 flex items-center justify-between">
              <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-purple-300">マトリクス健全度スコア</div>
                <div class="text-2xl font-black text-white mt-0.5 flex items-baseline gap-1">
                  <span>{{ coachResult.quadrant_health_score }}</span>
                  <span class="text-xs text-slate-400 font-normal">/ 100 pt</span>
                </div>
                <div class="text-xs font-bold text-purple-200 mt-1">{{ coachResult.headline }}</div>
              </div>
              <div class="w-16 h-16 rounded-full bg-purple-500/10 border-2 border-purple-500 flex items-center justify-center text-xl shadow-lg shadow-purple-500/20">
                <span v-if="coachResult.quadrant_health_score >= 80">🌟</span>
                <span v-else-if="coachResult.quadrant_health_score >= 60">⚡</span>
                <span v-else>⚠️</span>
              </div>
            </div>

            <!-- Quadrant Distribution Mini Bar -->
            <div v-if="coachResult.stats" class="p-3 bg-slate-950 rounded-xl border border-slate-800 space-y-1.5">
              <div class="flex justify-between text-[11px] text-slate-400">
                <span>象限バランス (総数: {{ coachResult.stats.total }}件)</span>
                <span>第2象限: {{ coachResult.stats.plan }}件</span>
              </div>
              <div class="w-full h-2.5 rounded-full bg-slate-800 flex overflow-hidden">
                <div
                  :style="{ width: `${coachResult.stats.total ? (coachResult.stats.do / coachResult.stats.total) * 100 : 0}%` }"
                  class="bg-rose-500"
                  title="第1象限 (DO)"
                ></div>
                <div
                  :style="{ width: `${coachResult.stats.total ? (coachResult.stats.plan / coachResult.stats.total) * 100 : 0}%` }"
                  class="bg-sky-500"
                  title="第2象限 (PLAN)"
                ></div>
                <div
                  :style="{ width: `${coachResult.stats.total ? (coachResult.stats.delegate / coachResult.stats.total) * 100 : 0}%` }"
                  class="bg-amber-500"
                  title="第3象限 (DELEGATE)"
                ></div>
                <div
                  :style="{ width: `${coachResult.stats.total ? (coachResult.stats.eliminate / coachResult.stats.total) * 100 : 0}%` }"
                  class="bg-slate-500"
                  title="第4象限 (ELIMINATE)"
                ></div>
              </div>
              <div class="flex justify-between text-[10px] text-slate-400 pt-1">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span>第1: {{ coachResult.stats.do }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-sky-500"></span>第2: {{ coachResult.stats.plan }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span>第3: {{ coachResult.stats.delegate }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-500"></span>第4: {{ coachResult.stats.eliminate }}</span>
              </div>
            </div>

            <!-- Insights -->
            <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/80 text-xs text-slate-300 space-y-1">
              <div class="font-bold text-slate-200 flex items-center gap-1">
                <span>🔍 分析インサイト</span>
              </div>
              <p class="leading-relaxed">{{ coachResult.insights }}</p>
            </div>

            <!-- Recommended Action -->
            <div class="p-3.5 rounded-xl bg-purple-900/30 border border-purple-500/30 text-xs text-purple-200 space-y-1">
              <div class="font-bold text-purple-300 flex items-center gap-1">
                <span>💡 推奨アクション</span>
              </div>
              <p class="leading-relaxed">{{ coachResult.recommended_action }}</p>
            </div>
          </div>

          <div class="pt-2 flex justify-end border-t border-slate-800">
            <button
              type="button"
              @click="isCoachModalOpen = false"
              class="px-5 py-2 rounded-lg text-sm font-semibold bg-slate-800 hover:bg-slate-700 text-white transition"
            >
              閉じる
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useTasks } from '~/composables/useTasks';
import { useProjects } from '~/composables/useProjects';
import { useAi } from '~/composables/useAi';
import type { PriorityType, TaskScope, Methodology, WaterfallPhase, Task } from '~/types/task';
import type { Project } from '~/types/project';

useHead({
  title: '全体ダッシュボード＆タスクマネージャー | Nuxt 3 × Laravel 11',
  meta: [
    { name: 'description', content: '全体ダッシュボードからプロジェクトダッシュボード、チームタスク（アジャイル/WBS）、個人の4象限マトリクスを直結' }
  ]
});

// Composable instances
const {
  tasks,
  teamTasks,
  personalTasks,
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

const {
  projects,
  currentProject,
  projectSummary,
  fetchProjects,
  fetchProjectSummary,
  createProject,
  deleteProject,
  selectProject,
} = useProjects();

const {
  isTriaging,
  isBreakingDown,
  isCoaching,
  triageResult,
  breakdownResult,
  coachResult,
  fetchTriage,
  fetchBreakdown,
  fetchCoach,
  clearTriage,
  clearBreakdown,
} = useAi();

// --- Navigation View State ---
// 'global' (全体ダッシュボード) | 'project' (プロジェクト詳細) | 'team' (チームタスク) | 'personal' (個人4象限)
const currentView = ref<'global' | 'project' | 'team' | 'personal'>('global');
const selectedMethodology = ref<Methodology>('agile');

const navigateTo = (view: 'global' | 'project' | 'team' | 'personal') => {
  currentView.value = view;
};

// Filtered tasks based on currentProject
const filteredTasks = computed(() => {
  if (!currentProject.value) return tasks.value;
  return tasks.value.filter((t) => t.project_id === currentProject.value?.id);
});

const currentTeamTasks = computed(() => filteredTasks.value.filter((t) => t.task_scope === 'team'));
const currentPersonalTasks = computed(() => filteredTasks.value.filter((t) => t.task_scope === 'personal'));

// Agile & Waterfall filtered by project
const currentAgileTasks = computed(() => currentTeamTasks.value.filter((t) => t.methodology === 'agile'));
const agileColumns = computed(() => ({
  todo: currentAgileTasks.value.filter((t) => t.status === 'todo'),
  in_progress: currentAgileTasks.value.filter((t) => t.status === 'in_progress'),
  review: currentAgileTasks.value.filter((t) => t.status === 'review'),
  done: currentAgileTasks.value.filter((t) => t.status === 'done'),
}));

const currentWaterfallTasks = computed(() => currentTeamTasks.value.filter((t) => t.methodology === 'waterfall'));
const currentWaterfallPhases = computed(() => ({
  requirement: currentWaterfallTasks.value.filter((t) => t.waterfall_phase === 'requirement'),
  design: currentWaterfallTasks.value.filter((t) => t.waterfall_phase === 'design'),
  development: currentWaterfallTasks.value.filter((t) => t.waterfall_phase === 'development'),
  testing: currentWaterfallTasks.value.filter((t) => t.waterfall_phase === 'testing'),
  release: currentWaterfallTasks.value.filter((t) => t.waterfall_phase === 'release'),
}));

// Personal Matrix filtered by project
const currentQuadrants = computed(() => ({
  urgent_important: currentPersonalTasks.value.filter((t) => t.priority_type === 'urgent_important'),
  not_urgent_important: currentPersonalTasks.value.filter((t) => t.priority_type === 'not_urgent_important'),
  urgent_not_important: currentPersonalTasks.value.filter((t) => t.priority_type === 'urgent_not_important'),
  not_urgent_not_important: currentPersonalTasks.value.filter((t) => t.priority_type === 'not_urgent_not_important'),
}));

const totalStoryPoints = computed(() => {
  return currentAgileTasks.value.reduce((sum, t) => sum + (t.agile_story_points || 0), 0);
});

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

// Selection helper
const selectAndGoToProject = (project: Project) => {
  selectProject(project);
  selectedMethodology.value = project.methodology || 'agile';
  currentView.value = 'project';
};

const goToTeamTasks = () => {
  if (currentProject.value) {
    selectedMethodology.value = currentProject.value.methodology || 'agile';
  }
  currentView.value = 'team';
};

const goToPersonalMatrix = () => {
  currentView.value = 'personal';
};

const handleProjectDropdownChange = (event: Event) => {
  const val = (event.target as HTMLSelectElement).value;
  if (!val) {
    selectProject(null);
  } else {
    const found = projects.value.find((p) => p.id === Number(val));
    if (found) {
      selectProject(found);
      selectedMethodology.value = found.methodology || 'agile';
    }
  }
};

const confirmDeleteProject = async (project: Project) => {
  if (confirm(`プロジェクト「${project.name}」を削除してもよろしいですか？\n紐づくタスクもすべて削除されます。`)) {
    await deleteProject(project.id);
  }
};

const refreshAll = async () => {
  await Promise.all([
    fetchProjects(),
    fetchProjectSummary(),
    fetchTasks(),
  ]);
};

// --- 日時ヘルパー ---
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

// --- プロジェクト新規作成モーダル ---
const isCreateProjectModalOpen = ref(false);
const newProjectForm = ref<{
  name: string;
  description: string;
  methodology: Methodology;
  color: string;
  start_date: string;
  end_date: string;
}>({
  name: '',
  description: '',
  methodology: 'agile',
  color: '#6366f1',
  start_date: '',
  end_date: '',
});

const submitCreateProject = async () => {
  if (!newProjectForm.value.name) return;

  const created = await createProject({
    name: newProjectForm.value.name,
    description: newProjectForm.value.description || null,
    methodology: newProjectForm.value.methodology,
    color: newProjectForm.value.color,
    start_date: newProjectForm.value.start_date || null,
    end_date: newProjectForm.value.end_date || null,
  });

  if (created) {
    isCreateProjectModalOpen.value = false;
    newProjectForm.value = {
      name: '',
      description: '',
      methodology: 'agile',
      color: '#6366f1',
      start_date: '',
      end_date: '',
    };
    // 作成したプロジェクトのダッシュボードへ遷移
    selectAndGoToProject(created);
  }
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
  clearBreakdown();
  clearTriage();
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
    currentView.value = 'personal';
  }
};

// --- タスク新規登録モーダル ---
const isCreateModalOpen = ref(false);
const createTaskForm = ref<{
  project_id: number | null;
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
  project_id: null,
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
  clearTriage();
  createTaskForm.value = {
    project_id: defaults.project_id ?? (currentProject.value?.id || null),
    task_scope: defaults.task_scope || (currentView.value === 'personal' ? 'personal' : 'team'),
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
    project_id: createTaskForm.value.project_id,
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
    await fetchProjects(); // update task count
  }
};

// --- タスク内容編集モーダル ---
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
  clearTriage();
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
    await fetchProjects();
  }
};

const deleteCurrentEditingTask = async () => {
  if (!editingTaskId.value) return;
  const success = await deleteTask(editingTaskId.value);
  if (success) {
    isEditModalOpen.value = false;
    await fetchProjects();
  }
};

const handleQuadrantChange = (task: Task, event: Event) => {
  const target = event.target as HTMLSelectElement;
  changeQuadrant(task, target.value as PriorityType);
};

// --- AI アクション ---
const isCoachModalOpen = ref(false);

const openCoachModal = async () => {
  isCoachModalOpen.value = true;
  await fetchCoach(currentProject.value?.id || null, currentView.value === 'personal' ? 'personal' : 'team');
};

const runAiTriageForCreate = async () => {
  if (!createTaskForm.value.title) return;
  const res = await fetchTriage(
    createTaskForm.value.title,
    createTaskForm.value.description,
    toJstFormattedString(createTaskForm.value.due_date) || undefined,
    createTaskForm.value.task_scope
  );
  if (res) {
    createTaskForm.value.priority_type = res.priority_type;
  }
};

const runAiTriageForEdit = async () => {
  if (!editTaskForm.value.title) return;
  const res = await fetchTriage(
    editTaskForm.value.title,
    editTaskForm.value.description,
    toJstFormattedString(editTaskForm.value.due_date) || undefined,
    'personal'
  );
  if (res) {
    editTaskForm.value.priority_type = res.priority_type;
  }
};

const runAiBreakdownForBranch = async () => {
  if (!targetTeamTask.value) return;
  await fetchBreakdown(
    targetTeamTask.value.title,
    targetTeamTask.value.description || undefined,
    targetTeamTask.value.methodology
  );
};

const importBreakdownSubtasks = async () => {
  if (!breakdownResult.value || !targetTeamTask.value) return;
  for (const sub of breakdownResult.value.subtasks) {
    await createTask({
      project_id: targetTeamTask.value.project_id,
      task_scope: 'personal',
      methodology: 'matrix',
      team_task_id: targetTeamTask.value.id,
      title: sub.title,
      description: sub.description,
      priority_type: sub.priority_type,
      due_date: toJstFormattedString(branchForm.value.due_date),
    });
  }
  clearBreakdown();
  isBranchModalOpen.value = false;
  currentView.value = 'personal';
  await fetchTasks();
  await fetchProjects();
};

onMounted(() => {
  refreshAll();
});
</script>
