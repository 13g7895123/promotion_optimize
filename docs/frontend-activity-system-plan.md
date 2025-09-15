# 前台活動系統規劃

## 系統概述

前台活動系統旨在為遊戲伺服器提供豐富多樣的玩家活動體驗，包含三大核心活動類型：累積型活動、連續型活動、和時限型活動。系統設計注重用戶體驗、互動性和獎勞機制的完整性。

## 核心活動類型

### 1. 累積型活動 (Accumulative Activities)

**特色：**
- 長期進行的活動，玩家累積達成條件可獲得獎勵
- 支援階段性獎勵，鼓勵持續參與
- 具備進度追蹤和視覺化展示

**活動示例：**
- **推薦達人活動**：累積推薦指定人數獲得獎勵
- **消費積分活動**：累積消費金額達標獲得禮品
- **在線時數活動**：累積遊戲時間獲得經驗加成

**實現要素：**
```typescript
interface AccumulativeActivity {
  id: string
  title: string
  description: string
  type: 'accumulative'
  target_value: number      // 目標累積值
  current_progress: number  // 當前進度
  reward_tiers: RewardTier[] // 階段性獎勵
  start_date: Date
  end_date: Date
  status: 'active' | 'completed' | 'expired'
}

interface RewardTier {
  threshold: number  // 達成門檻
  rewards: Reward[]  // 獎勵內容
  claimed: boolean   // 是否已領取
}
```

### 2. 連續型活動 (Sequential Activities)

**特色：**
- 要求玩家連續完成特定行為
- 中斷連續記錄會重置進度
- 提供連續獎勵加成機制

**活動示例：**
- **每日簽到活動**：連續登入獲得遞增獎勵
- **連續推薦活動**：連續推薦好友獲得額外獎勵
- **連續在線活動**：連續每日在線指定時間

**實現要素：**
```typescript
interface SequentialActivity {
  id: string
  title: string
  description: string
  type: 'sequential'
  required_sequence: number     // 需要連續次數
  current_streak: number        // 當前連續次數
  max_streak_achieved: number   // 歷史最高連續
  daily_rewards: DailyReward[]  // 每日獎勵配置
  bonus_rewards: BonusReward[]  // 連續獎勵加成
  last_participation: Date      // 最後參與時間
  reset_time: string           // 重置時間 (HH:mm)
}

interface DailyReward {
  day: number      // 第幾天
  rewards: Reward[] // 當日獎勵
  multiplier?: number // 獎勵倍數
}
```

### 3. 時限型活動 (Time-Limited Activities)

**特色：**
- 限時開放的特殊活動
- 具備倒數計時和緊迫感
- 獨特獎勵和限量物品

**活動示例：**
- **快閃推薦活動**：限時24小時推薦獎勵雙倍
- **週末特惠活動**：週末限定的特殊獎勵
- **節慶限定活動**：配合節日的主題活動

**實現要素：**
```typescript
interface TimeLimitedActivity {
  id: string
  title: string
  description: string
  type: 'time_limited'
  start_time: Date
  end_time: Date
  countdown_display: boolean    // 是否顯示倒數計時
  participation_limit: number   // 參與次數限制
  current_participants: number  // 當前參與人數
  max_participants?: number     // 最大參與人數
  special_rewards: Reward[]     // 特殊獎勵
  requirements: ActivityRequirement[] // 參與條件
}
```

## 技術架構設計

### 前端架構

```
Frontend Activity System
├── Activity Dashboard (活動總覽)
│   ├── Featured Activities (精選活動)
│   ├── My Activities (我的活動)
│   └── Activity Calendar (活動日曆)
│
├── Activity Pages (活動頁面)
│   ├── Activity Detail (活動詳情)
│   ├── Progress Tracking (進度追蹤)
│   ├── Leaderboard (排行榜)
│   └── Reward Claims (獎勵領取)
│
├── Interactive Components (互動組件)
│   ├── Progress Bars (進度條)
│   ├── Countdown Timers (倒數計時器)
│   ├── Achievement Badges (成就徽章)
│   └── Social Sharing (社交分享)
│
└── Notification System (通知系統)
    ├── Activity Reminders (活動提醒)
    ├── Reward Notifications (獎勵通知)
    └── Status Updates (狀態更新)
```

### 組件設計規範

#### 1. Activity Card Component
```vue
<template>
  <div class="activity-card" :class="activityStatusClass">
    <div class="activity-header">
      <div class="activity-icon">
        <component :is="activityIcon" />
      </div>
      <div class="activity-meta">
        <h3 class="activity-title">{{ activity.title }}</h3>
        <p class="activity-description">{{ activity.description }}</p>
      </div>
      <div class="activity-status">
        <StatusBadge :status="activity.status" />
      </div>
    </div>

    <div class="activity-progress">
      <ProgressDisplay :activity="activity" />
    </div>

    <div class="activity-rewards">
      <RewardPreview :rewards="activity.current_rewards" />
    </div>

    <div class="activity-actions">
      <button @click="participateActivity" :disabled="!canParticipate">
        {{ actionButtonText }}
      </button>
    </div>
  </div>
</template>
```

#### 2. Progress Tracking Components
```vue
<!-- Accumulative Progress -->
<AccumulativeProgress
  :current="activity.current_progress"
  :target="activity.target_value"
  :tiers="activity.reward_tiers"
/>

<!-- Sequential Progress -->
<SequentialProgress
  :streak="activity.current_streak"
  :required="activity.required_sequence"
  :daily-rewards="activity.daily_rewards"
/>

<!-- Time Limited Progress -->
<TimeLimitedProgress
  :end-time="activity.end_time"
  :participants="activity.current_participants"
  :limit="activity.max_participants"
/>
```

### 狀態管理架構

```typescript
// Pinia Store for Activity System
export const useActivityStore = defineStore('activity', {
  state: () => ({
    activities: [] as Activity[],
    userActivities: [] as UserActivity[],
    activeActivity: null as Activity | null,
    notifications: [] as ActivityNotification[],
    loading: false,
    error: null as string | null
  }),

  getters: {
    featuredActivities: (state) =>
      state.activities.filter(a => a.featured),

    activeActivities: (state) =>
      state.activities.filter(a => a.status === 'active'),

    upcomingActivities: (state) =>
      state.activities.filter(a => a.start_time > new Date()),

    userParticipatingActivities: (state) =>
      state.userActivities.filter(ua => ua.status === 'participating')
  },

  actions: {
    async fetchActivities() { /* ... */ },
    async participateActivity(activityId: string) { /* ... */ },
    async claimReward(activityId: string, rewardId: string) { /* ... */ },
    async updateProgress(activityId: string, progress: number) { /* ... */ }
  }
})
```

## 用戶體驗設計

### 1. 視覺設計原則

**色彩系統：**
- 累積型活動：藍色系 (#3B82F6) - 代表穩定成長
- 連續型活動：綠色系 (#10B981) - 代表持續進行
- 時限型活動：橙色系 (#F59E0B) - 代表緊迫性

**動畫效果：**
- 進度條動畫：平滑的進度增長動畫
- 獎勵特效：領取獎勵時的慶祝動畫
- 倒數計時：緊迫感的脈衝動畫

### 2. 互動體驗設計

**進度反饋：**
```vue
<template>
  <div class="progress-container">
    <!-- 進度條 -->
    <div class="progress-bar">
      <div
        class="progress-fill"
        :style="{ width: progressPercentage + '%' }"
        @animationend="onProgressComplete"
      ></div>
    </div>

    <!-- 里程碑標記 -->
    <div class="milestones">
      <div
        v-for="milestone in milestones"
        :key="milestone.id"
        class="milestone"
        :class="{
          completed: currentProgress >= milestone.threshold,
          claimed: milestone.claimed
        }"
        @click="claimMilestone(milestone)"
      >
        <span class="milestone-value">{{ milestone.threshold }}</span>
        <div class="milestone-reward">
          <RewardIcon :reward="milestone.reward" />
        </div>
      </div>
    </div>
  </div>
</template>
```

**獎勵展示：**
```vue
<template>
  <div class="reward-showcase">
    <div
      v-for="reward in rewards"
      :key="reward.id"
      class="reward-item"
      :class="{ available: reward.available }"
    >
      <div class="reward-icon">
        <img :src="reward.icon" :alt="reward.name" />
        <div v-if="reward.quantity > 1" class="quantity-badge">
          {{ reward.quantity }}
        </div>
      </div>
      <div class="reward-info">
        <h4 class="reward-name">{{ reward.name }}</h4>
        <p class="reward-description">{{ reward.description }}</p>
      </div>
      <button
        v-if="reward.available && !reward.claimed"
        @click="claimReward(reward)"
        class="claim-button"
      >
        領取
      </button>
    </div>
  </div>
</template>
```

### 3. 響應式設計

**行動裝置優化：**
- 滑動操作：左右滑動切換活動
- 觸控優化：按鈕大小適合觸控操作
- 垂直布局：適應小螢幕的垂直滾動

**桌面端功能：**
- 快捷鍵：鍵盤快捷鍵快速導航
- 拖拽操作：拖拽排序個人活動
- 多窗口：支援多個活動同時查看

## 社交功能整合

### 1. 好友互動系統

**好友活動動態：**
```vue
<template>
  <div class="friend-activities">
    <h3>好友動態</h3>
    <div
      v-for="activity in friendActivities"
      :key="activity.id"
      class="friend-activity-item"
    >
      <div class="friend-avatar">
        <img :src="activity.friend.avatar" />
      </div>
      <div class="activity-content">
        <p>
          <strong>{{ activity.friend.name }}</strong>
          {{ formatActivityAction(activity.action) }}
          <span class="activity-name">{{ activity.activity_name }}</span>
        </p>
        <div class="activity-time">{{ formatTime(activity.timestamp) }}</div>
      </div>
      <div class="activity-actions">
        <button @click="joinActivity(activity.activity_id)">
          我也參加
        </button>
      </div>
    </div>
  </div>
</template>
```

### 2. 排行榜系統

**活動排行榜：**
```typescript
interface Leaderboard {
  activity_id: string
  period: 'daily' | 'weekly' | 'monthly' | 'all_time'
  entries: LeaderboardEntry[]
  user_rank?: number
  total_participants: number
}

interface LeaderboardEntry {
  rank: number
  user: {
    id: string
    name: string
    avatar: string
    server: string
  }
  score: number
  progress: number
  rewards_earned: number
}
```

### 3. 分享功能

**成就分享：**
```vue
<template>
  <div class="achievement-share">
    <div class="share-content">
      <div class="achievement-display">
        <div class="achievement-icon">
          <img :src="achievement.icon" />
        </div>
        <div class="achievement-info">
          <h3>{{ achievement.title }}</h3>
          <p>{{ achievement.description }}</p>
        </div>
      </div>

      <div class="share-buttons">
        <button @click="shareToFacebook" class="share-facebook">
          分享到 Facebook
        </button>
        <button @click="shareToLine" class="share-line">
          分享到 LINE
        </button>
        <button @click="copyShareLink" class="share-link">
          複製連結
        </button>
      </div>
    </div>
  </div>
</template>
```

## 通知系統設計

### 1. 實時通知

**通知類型：**
```typescript
type NotificationType =
  | 'activity_started'     // 活動開始
  | 'activity_ending'      // 活動即將結束
  | 'progress_milestone'   // 進度里程碑
  | 'reward_available'     // 獎勵可領取
  | 'streak_reminder'      // 連續提醒
  | 'friend_joined'        // 好友參加活動

interface ActivityNotification {
  id: string
  type: NotificationType
  title: string
  message: string
  activity_id: string
  user_id: string
  timestamp: Date
  read: boolean
  action_url?: string
  priority: 'low' | 'medium' | 'high'
}
```

### 2. 推送策略

**智能推送時機：**
- 活動開始前 30 分鐘提醒
- 連續活動中斷風險提醒
- 獎勵即將過期提醒
- 好友活動邀請

**個人化設定：**
```vue
<template>
  <div class="notification-settings">
    <h3>通知設定</h3>

    <div class="notification-category">
      <h4>活動通知</h4>
      <label>
        <input v-model="settings.activity_reminders" type="checkbox" />
        活動開始提醒
      </label>
      <label>
        <input v-model="settings.progress_updates" type="checkbox" />
        進度更新通知
      </label>
      <label>
        <input v-model="settings.reward_alerts" type="checkbox" />
        獎勵提醒
      </label>
    </div>

    <div class="notification-timing">
      <h4>提醒時間</h4>
      <select v-model="settings.reminder_time">
        <option value="immediate">立即</option>
        <option value="15min">15分鐘前</option>
        <option value="30min">30分鐘前</option>
        <option value="1hour">1小時前</option>
      </select>
    </div>
  </div>
</template>
```

## 資料分析與優化

### 1. 用戶行為追蹤

**關鍵指標：**
```typescript
interface ActivityAnalytics {
  activity_id: string

  // 參與率指標
  participation_rate: number      // 參與率
  completion_rate: number         // 完成率
  retention_rate: number          // 留存率

  // 互動指標
  average_session_time: number    // 平均停留時間
  click_through_rate: number      // 點擊率
  share_rate: number              // 分享率

  // 獎勵指標
  reward_claim_rate: number       // 獎勵領取率
  reward_value_distributed: number // 已發放獎勵價值

  // 時間分析
  peak_activity_hours: number[]   // 活躍時段
  daily_participation: DailyStats[] // 每日參與統計
}
```

### 2. A/B 測試框架

**實驗配置：**
```typescript
interface ActivityExperiment {
  id: string
  name: string
  description: string
  variants: ExperimentVariant[]
  traffic_allocation: number      // 流量分配比例
  start_date: Date
  end_date: Date
  success_metrics: string[]       // 成功指標
  status: 'draft' | 'running' | 'completed'
}

interface ExperimentVariant {
  id: string
  name: string
  configuration: ActivityConfiguration
  allocation_percentage: number
  performance_metrics: VariantMetrics
}
```

## 實作時程規劃

### Phase 1: 基礎架構 (2-3週)
- [ ] 設置前端項目結構
- [ ] 實現基礎組件庫
- [ ] 建立狀態管理系統
- [ ] 設計響應式布局

### Phase 2: 核心功能 (3-4週)
- [ ] 實現三種活動類型
- [ ] 開發進度追蹤系統
- [ ] 建立獎勵管理機制
- [ ] 實現用戶參與功能

### Phase 3: 互動功能 (2-3週)
- [ ] 實現社交功能
- [ ] 開發排行榜系統
- [ ] 建立分享機制
- [ ] 實現通知系統

### Phase 4: 優化整合 (2週)
- [ ] 性能優化
- [ ] 整合測試
- [ ] 用戶體驗優化
- [ ] 分析系統整合

### Phase 5: 部署上線 (1週)
- [ ] 生產環境部署
- [ ] 監控系統設置
- [ ] 用戶反饋收集
- [ ] 持續優化迭代

## 技術選型建議

### 前端技術棧
- **框架**：Nuxt.js 3 (已確定)
- **狀態管理**：Pinia
- **UI 組件**：自定義組件庫 + Tailwind CSS
- **動畫**：Vue Transition + CSS Animations
- **圖表**：Chart.js (已使用)
- **時間處理**：Day.js
- **通知**：Web Push API + Socket.io

### 開發工具
- **測試**：Vitest + Vue Test Utils
- **代碼品質**：ESLint + Prettier
- **類型檢查**：TypeScript
- **建構工具**：Vite (Nuxt 內建)

## 總結

本前台活動系統規劃涵蓋了完整的用戶活動體驗，從基礎的活動參與到高級的社交互動，提供了全方位的技術架構和實現方案。系統設計重視用戶體驗、可擴展性和維護性，能夠支援遊戲伺服器推廣平台的長期發展需求。

透過模組化的設計和完善的技術選型，此活動系統能夠與現有的後台管理系統完美整合，為整個推廣平台提供強大的活動運營能力。