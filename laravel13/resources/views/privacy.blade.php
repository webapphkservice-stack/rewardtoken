@php
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('site_name_full')) ?> — 隐私政策</title>
    <link rel="stylesheet" href="/css/app.css?v=20261024logo">
    <script src="/js/app.js?v=6" defer></script>
    <style>
        .legal-page { max-width: 760px; margin: 40px auto; padding: 0 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif; color: #222; line-height: 1.75; }
        .legal-page h1 { font-size: 26px; font-weight: 700; margin: 0 0 8px; }
        .legal-page .meta { color: #888; font-size: 13px; margin-bottom: 28px; }
        .legal-page h2 { font-size: 18px; font-weight: 600; margin: 28px 0 12px; padding-left: 10px; border-left: 3px solid #285793; }
        .legal-page p { margin: 10px 0; font-size: 15px; }
        .legal-page ul { margin: 10px 0 10px 24px; padding: 0; font-size: 15px; }
        .legal-page li { margin: 4px 0; }
        .legal-page .nav-back { display: inline-block; margin-bottom: 16px; color: #285793; text-decoration: none; font-size: 14px; }
        .legal-page .nav-back:hover { text-decoration: underline; }
        .legal-page table { border-collapse: collapse; width: 100%; margin: 12px 0; font-size: 14px; }
        .legal-page th, .legal-page td { border: 1px solid #ddd; padding: 10px 12px; text-align: left; vertical-align: top; }
        .legal-page th { background: #f5f7fa; font-weight: 600; }
        .legal-page tr:nth-child(even) td { background: #fafbfc; }
    </style>
</head>
<body>
    <div class="legal-page">
        @include('partials.topbar')
        <a class="nav-back" href="/">← 返回首页</a>
        <h1>隐私政策</h1>
        <p class="meta">最后更新日期：2026 年 10 月 21 日</p>

        <p>本隐私政策说明了邀请奖励代币（以下简称"本服务"）如何收集、使用、存储和保护您的个人信息，以及您在数据方面享有的权利。请您在使用本服务前仔细阅读。使用本服务即表示您同意本政策所描述的数据处理方式。</p>

        <h2>一、我们收集哪些信息</h2>
        <p>本服务仅收集为履行服务功能所必需的最小信息集，具体如下：</p>

        <table>
            <tr>
                <th>信息类别</th>
                <th>具体内容</th>
                <th>收集目的</th>
            </tr>
            <tr>
                <td>账户信息</td>
                <td>注册用户名/邮箱、加密后的登录密码</td>
                <td>身份验证与账户安全</td>
            </tr>
            <tr>
                <td>钱包地址</td>
                <td>您绑定的以太坊钱包地址（0x 开头的 42 位字符串）</td>
                <td>代币接收地址识别、链上交易记录关联</td>
            </tr>
            <tr>
                <td>交易信息</td>
                <td>购买订单号、支付金额、代币数量、邀请关系、链上交易哈希</td>
                <td>交易对账、订单查询、奖励结算</td>
            </tr>
            <tr>
                <td>支付信息</td>
                <td>支付网关返回的交易 ID、支付状态、支付时间（本服务不存储银行卡号、CVV 等敏感支付信息）</td>
                <td>支付结果回调确认、订单状态更新</td>
            </tr>
            <tr>
                <td>技术日志</td>
                <td>IP 地址、访问时间、请求路径、设备信息、Session Cookie</td>
                <td>服务运维、安全审计、异常排查、防滥用</td>
            </tr>
        </table>

        <h2>二、我们如何使用这些信息</h2>
        <p>收集到的信息仅用于以下明确目的，<strong>不会</strong>用于未经说明的用途：</p>
        <ul>
            <li>提供、维护和改进本服务的核心功能（账户、代币、购买、转账、邀请奖励）；</li>
            <li>处理支付、链上交易和客户查询；</li>
            <li>保障账户安全，包括异常登录检测、滥用行为识别；</li>
            <li>遵守法律法规要求，回应合法的政府或监管请求；</li>
            <li>聚合后的匿名统计，用于产品优化（不会对外泄露可识别个人的原始数据）。</li>
        </ul>

        <h2>三、信息存储与安全</h2>
        <p>3.1 本服务运行于托管服务器上，您的账户信息、订单信息和交易记录存储于服务器的 MySQL 数据库中，链上交易数据存储于私有 Ethereum 兼容节点（Geth）。</p>
        <p>3.2 登录密码使用 bcrypt 等行业标准算法进行哈希存储，即使数据库泄露也无法直接还原明文。</p>
        <p>3.3 本服务的所有公开网络接口均通过 HTTPS 加密传输，防止数据在传输过程中被窃听或篡改。</p>
        <p>3.4 我们采用合理的技术与管理措施保护您的数据安全，但请注意：<strong>没有任何互联网传输或存储方法是 100% 安全的</strong>。对于超越我们合理控制范围的安全事件，我们不承担责任。</p>

        <h2>四、链上数据的特殊性</h2>
        <p>4.1 本服务运行于<strong>私有链</strong>，与公有链不同。链上记录虽然对参与节点公开可见，但节点由平台统一控制，一般互联网用户无法直接访问链上数据。</p>
        <p>4.2 一旦代币被发送至您的钱包地址，该交易哈希会被写入链上。交易哈希和地址本身构成可识别个人与交易行为的关联数据。</p>
        <p>4.3 由于区块链技术特性，链上数据<strong>一旦写入不可删除</strong>。请在决定绑定钱包地址前了解这一点。</p>

        <h2>五、第三方共享</h2>
        <p>本服务仅在以下必要场景与第三方共享数据：</p>
        <ul>
            <li><strong>支付网关（gmpay-edge）</strong>：我们将您的订单号、支付金额、订单创建时间提交至网关以完成支付处理。网关会独立处理您的支付信息，其隐私政策适用。<strong>本服务不向网关传输您的钱包地址</strong>。</li>
            <li><strong>监管合规请求</strong>：当收到有管辖权的政府机构、监管部门或执法机关的合法数据请求时，我们将在法律要求的范围内提供所请求的信息。</li>
        </ul>
        <p class="highlight" style="background:#f0f4ff;border-left:3px solid #285793;padding:12px 16px;margin:16px 0;border-radius:4px;">
            <strong>我们不会：</strong>将您的个人信息出售给第三方；将您的钱包地址或交易行为分享给任何广告商或数据分析公司；将您的数据用于任何未在本政策中说明的用途。
        </p>

        <h2>六、数据保留期限</h2>
        <p>6.1 账户信息：账户存续期间持续保留。账户终止后，除法律另有要求外，账户数据将在 12 个月后从活跃系统中移除。</p>
        <p>6.2 交易与订单信息：保留期限不低于 5 年，以满足审计、合规和争议解决需要。</p>
        <p>6.3 链上数据：随区块链存续，不可删除。</p>
        <p>6.4 技术日志：IP 和访问日志保留 90 天，超出后自动清理。</p>

        <h2>七、Cookie 与类似技术</h2>
        <p>本服务在您的浏览器或设备上使用以下技术：</p>
        <ul>
            <li><strong>会话 Cookie</strong>：在您登录后识别您的身份，防止他人冒用您的账户会话。Cookie 仅在浏览器会话期间有效。</li>
            <li><strong>语言偏好存储</strong>：移动端 App 使用本地存储记住您选择的界面语言。</li>
        </ul>
        <p>您可以通过浏览器设置拒绝或删除 Cookie，但这将导致您无法正常使用需要登录的功能。</p>

        <h2>八、未成年人保护</h2>
        <p>本服务<strong>不面向未成年人</strong>。我们不会有意收集不满 18 周岁未成年人的个人信息。如我们发现未经父母或监护人同意的未成年人使用本服务，我们将在合理可行的范围内尽快删除相关数据。</p>

        <h2>九、您的权利</h2>
        <p>根据适用法律，您对自己的个人信息享有以下权利：</p>
        <ul>
            <li><strong>访问权</strong>：您可以查看自己的账户信息、订单记录和交易历史（通过平台界面的"我的订单"等功能）。</li>
            <li><strong>更正权</strong>：您可以更正账户中可编辑的信息。</li>
            <li><strong>注销权</strong>：您可以申请注销账户。注销后我们将按第六条规定的期限处理您的数据。</li>
            <li><strong>投诉权</strong>：如您对数据处理方式有任何疑虑，可通过本页面底部的联系方式与我们沟通。</li>
        </ul>

        <h2>十、跨境数据传输</h2>
        <p>本服务的基础设施部署于中国境内，您的个人信息存储于中国境内的服务器上。如需跨境传输（例如配合海外监管机构的合法请求），我们将确保传输符合适用法律的充分性和安全要求。</p>

        <h2>十一、政策变更</h2>
        <p>我们可能根据业务发展、法律变更或安全需要不时修订本隐私政策。修订后的政策将在本页面公示，并更新"最后更新日期"。重大变更将在生效前通过网站或 App 的显著位置通知您。</p>

        <h2>十二、联系我们</h2>
        <p>如对本隐私政策有任何疑问、意见或建议，或希望行使您的相关权利，请通过网站底部提供的联系方式与我们取得联系。我们将在收到请求后的 15 个自然日内作出书面回应。</p>

        <p style="margin-top:40px;color:#888;font-size:13px;">— 邀请奖励代币运营方 —</p>
    </div>
</body>
</html>
