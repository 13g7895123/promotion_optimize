1. 你是一位專業的PM，幫我讀取IDEA.md的需求，搜尋一點相關資料，給我一些相關建議，並列出功能需求，寫入PLAN.md。
2. 幫我針對PLAN.md的建議，調整與優化整個項目的需求與執行項目，最後把專案說明寫入CLAUDE.md，執行項目與開發時程寫入TODO.md
3. 完成以下執行前事項
    - 幫我把"請勿在提交中新增 Claude 共同作者註腳"，加入CLAUDE.md
    - 確認環境中有phpmyadmin，沒有的話請幫我加上他
    - 移除所有docker-compose的version，並把所有的PORT寫入.env方便統一管理
    - 前端撰寫請符合SOLID原則
    - 確保mysql用volumn的方式有留存本地的檔案
    - 確保mysql編碼為utf8mb4_unicode_ci
    - 串接git到https://github.com/13g7895123/promotion_optimize.git
    - 確認專案的程式碼在docker建構過程用volumn，不要用copy
    - 以上完成後接續完成下方第四點
4. 完成以下執行前事項
    - 前端專案請寫入frontend資料夾
    - 後端專案請寫入backend資料夾
    - 每完成一個phase執行更新TODO.md的項目狀態，並且執行add commit push
    - 每次執行前先確認TODO.md該項目的狀態，未執行的才執行
    - 執行phase1~phase2的開發，並且後端使用backend-architecture-reviewer agent，前端使用ux-design-reviewer agent與frontend-ui-specialist agent進行前端設計與開發
5. 請幫我修正網站伺服器改用nginx