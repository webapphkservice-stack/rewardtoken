// 底部 Dock 交互（defer 加载，DOM 就绪后执行）
(function () {
    var nav = document.querySelector('.navbar');
    if (!nav) return;
    var toggle = nav.querySelector('.dock-toggle');
    var menu = nav.querySelector('.dock-menu');
    if (!toggle || !menu) return;

    // 点击汉堡按钮开/关
    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.classList.toggle('open');
    });

    // 点击菜单内链接后收起
    menu.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            menu.classList.remove('open');
        });
    });

    // 点击页面上其它区域时关闭菜单
    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target) && !toggle.contains(e.target)) {
            menu.classList.remove('open');
        }
    });

    // 底部 Dock 的语言图标按钮：弹出语言选择器
    var langBtn = nav.querySelector('.lang-btn');
    var langPopup = nav.querySelector('.lang-popup') || document.getElementById('langPopup');
    if (langBtn && langPopup) {
        langBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            langPopup.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!langPopup.contains(e.target) && !langBtn.contains(e.target)) {
                langPopup.classList.remove('open');
            }
        });
    }

    // ---- 登录框 ----
    var overlay = document.getElementById('loginOverlay');
    var loginClose = document.getElementById('loginClose');
    var loginSubmit = document.getElementById('loginSubmit');
    var loginAlert = document.getElementById('loginAlert');
    var loginUsername = document.getElementById('loginUsername');
    var loginPassword = document.getElementById('loginPassword');
    var myBtn = document.getElementById('dockMyBtn');

    function showLogin(msg) {
        if (loginAlert) {
            if (msg) { loginAlert.textContent = msg; loginAlert.style.display = 'block'; }
            else { loginAlert.style.display = 'none'; }
        }
        if (overlay) overlay.style.display = 'flex';
        if (loginUsername) loginUsername.focus();
    }
    function hideLogin() {
        if (overlay) overlay.style.display = 'none';
        if (loginAlert) loginAlert.style.display = 'none';
        if (loginPassword) loginPassword.value = '';
    }

    // 点击“我的”：先校验登录态，未登录则拦截并弹出登录框
    if (myBtn && overlay) {
        myBtn.addEventListener('click', function (e) {
            e.preventDefault();
            checkLogin('/my');
        });
    }

    // 汉堡菜单中需登录的入口（奖励信息 / 收发代币）
    document.querySelectorAll('.login-gate').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            checkLogin(link.getAttribute('href') || '/');
        });
    });

    function checkLogin(dest) {
        fetch('/auth/status').then(function (r) { return r.json(); }).then(function (d) {
            var loggedIn = d && d.data && d.data.logged_in;
            if (loggedIn) {
                window.location.href = dest;
            } else {
                showLogin();
            }
        }).catch(function () {
            showLogin();
        });
    }

    if (loginClose) {
        loginClose.addEventListener('click', hideLogin);
    }

    // 点击遮罩空白处关闭
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) hideLogin();
        });
    }

    // 提交登录
    if (loginSubmit) {
        loginSubmit.addEventListener('click', function () {
            if (loginAlert) loginAlert.style.display = 'none';
            var username = loginUsername ? loginUsername.value.trim() : '';
            var password = loginPassword ? loginPassword.value : '';
            if (!username || !password) {
                if (loginAlert) { loginAlert.textContent = '请输入用户名和密码'; loginAlert.style.display = 'block'; }
                return;
            }
            loginSubmit.disabled = true;
            fetch('/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password)
            }).then(function (r) { return r.json(); }).then(function (d) {
                loginSubmit.disabled = false;
                if (d && d.success) {
                    window.location.href = '/my';
                } else if (loginAlert) {
                    loginAlert.textContent = (d && d.message) ? d.message : '登录失败';
                    loginAlert.style.display = 'block';
                }
            }).catch(function () {
                loginSubmit.disabled = false;
                if (loginAlert) { loginAlert.textContent = '登录失败'; loginAlert.style.display = 'block'; }
            });
        });
    }

    // 回车提交
    if (loginPassword && loginSubmit) {
        loginPassword.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') loginSubmit.click();
        });
    }
})();