1. 完成以下功能
    - 幫我把.env對外port拉到最上面，我要統一更新
    - "每次任務完成，如果有連結git repo，都幫我依據規則提交"，寫入CLAUDE.md
    - 幫我把"add commit push，記得不要加上「Generated with Claude Code」的標記"，寫入CLAUDE.md
    - "git如果有commit的部分，內容請幫我依據基本規則加在前面例如(fix: 'content')"，寫入CLAUDE.md
2. 幫我實作CD的部分
3. 請檢查專案是否有git repo，有的話依規則執行add commit push
4. CD出現錯誤，請排除他，錯誤內容請看error/08161908.txt
5. 為甚麼任務完成沒有執行add commit push，幫我確認原因並加入CLAUDE.md，避免再次發生
6. CD出現錯誤，請排除他，錯誤內容請看error/08161923.txt
7. CD出現錯誤，請排除他，錯誤內容請看error/08161945.txt
8. 幫我調整ci-cd.yml的檔案，原先是laravel的，幫我改成ci4的
9. CD出現錯誤，請排除他，錯誤內容請看error/08162016.txt
10. 後端登入會出現錯誤，幫我確認是否為API端引起的，或是前後端串接的問題
11. 請幫我把後端直接改為domain"https://promotion.mercylife.cc/api"，用nginx一堆問題你又修不好
12. 幫我移除nginx這個服務，backend直接透過PORT對外即可
13. 前台已經完成，先幫我規畫所有後台功能，同時前台的活動也幫我規畫一下，完成後，逐步進行
14. 後台登入頁有CORS的問題，已封鎖跨來源請求: 同源政策不允許讀取 https://promotion.mercylife.cc/api/auth/login 的遠端資源。（原因: 缺少 CORS 'Access-Control-Allow-Origin' 檔頭）。狀態代碼: 404。，先幫我調整一下，我要有local開發的環境，所以幫我完整調整，且不影響推上去的遠端環境
15. Missing script: "dev:local"
16. 已封鎖跨來源請求: 同源政策不允許讀取 https://promotion.mercylife.cc/api/auth/login 的遠端資源。（原因: 缺少 CORS 'Access-Control-Allow-Origin' 檔頭）。狀態代碼: 404。