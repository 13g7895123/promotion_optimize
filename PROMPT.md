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
5. 完成以下事項
    - 請幫我修正網站伺服器改用nginx
    - 請安裝PHP intl擴展，並確保環境可以正常運行
    - 幫我更新TODO.md，讓我知道專案當前的進度
6. 完成以下事項
    - 前端開發是否都還沒完成
    - 以及Sprint 3的前後端是否還沒完成，幫我確認一下
    - 並且前端開發的部分先由ux-design-reviewer agent進行設計
    - 後由frontend-ui-specialist agent進行開發
    - 後端則由backend-architecture-reviewer agent執行
7. 幫我確認一下部屬的狀況，我需要與product-requirements-manager agent討論與確認當前的狀況，我用docker啟動專案是有問題的
8. 現在可以啟動了，但我沒有看到前端有對應的PORT，NGINX的PORT打上去也是顯示503
9. 我需要與product-requirements-manager agent討論一下，我希望網頁前台，給玩家推廣的首頁可以像這個網頁一樣"https://cs.pcgame.tw/identify/tdb"，一進去有個特效，然後用網址區分伺服器，並且輸入帳號即可進入推廣的階段，幫我依據這個需求進行調整，另外後台的設計部分，我想與frontend-ui-specialist agent討論一下，希望可以用admin_template這個專案的樣式當參考，最後，路由的部分幫我分開，/admin為管理介面的，根目錄惟一般玩家使用的
10. 為甚麼跑一跑他會erro，"The requested module 'http://localhost:9117/_nuxt/node_modules/.cache/vite/client/deps/@element-plus_icons-vue.js?v=85ad53bf' doesn't provide an export named: 'Server'"，另外，前台為獨立頁面，不要出現後台的東西
11. 請移除default layout的東西，這樣server頁面會吃到他
12. 幫我完成以下項目
    - 輸入遊戲帳號的輸入框，文字與底色相同，請幫我調整
    - 點下開始推廣後，應該要直接到連結或是圖片推廣的地方才對，前台是沒有登入功能的，幫我依這個規則修正所有相關文件與功能
13. 幫我調整一下登入頁面，好醜，而且你沒有提供帳號密碼讓我登入
14. 目前前端與後端的溝通是有問題的，請重新確認一下API路徑是否正確，或是是其他原因導致無法使用的，請排除這個錯誤
15. 完成以下項目
    - 請幫我移除根目錄的內容，並弄一個精美的404給他
    - 輸入帳號的部分可以直接幫我改字體顏色就好嗎，不要改灰底背景，很醜
    - 為甚麼我點開始推廣後他沒有正常顯示應該要顯示的頁面