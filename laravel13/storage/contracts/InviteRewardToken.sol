// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

/**
 * 邀请制奖励代币 - Invite Reward Token
 * 
 * 功能：
 * 1. ERC-20 代币标准
 * 2. 邀请注册机制 - 新用户通过邀请人地址注册
 * 3. 多级推荐奖励 - 邀请人自动获得代币奖励，奖励直接转入钱包
 * 4. 奖励池管理 - 从总供应量中划出奖励池
 */
contract InviteRewardToken {
    // ============ 代币基本信息 ============
    string public name;
    string public symbol;
    uint8 public constant decimals = 18;

    // ============ 代币核心数据 ============
    uint256 public totalSupply;
    mapping(address => uint256) public balanceOf;
    mapping(address => mapping(address => uint256)) public allowance;

    // ============ 邀请系统数据 ============
    mapping(address => address) public inviterOf;          // 用户的邀请人
    mapping(address => uint256) public inviteCount;        // 用户的直接邀请人数
    mapping(address => bool) public isRegistered;          // 是否已注册
    mapping(address => uint256) public totalRewardsEarned;  // 用户累计获得的奖励

    // ============ 奖励配置 ============
    uint256 public inviteReward;                // 每次邀请的奖励总额
    uint256[3] public referralLevels;           // 三级推荐比例 [L1, L2, L3]
    uint256 public rewardPool;                  // 奖励池余额
    uint256 public rewardPoolInitial;           // 奖励池初始金额

    // ============ 管理员 ============
    address public admin;

    // ============ 事件 ============
    event Transfer(address indexed from, address indexed to, uint256 value);
    event Approval(address indexed owner, address indexed spender, uint256 value);
    event UserRegistered(address indexed user, address indexed inviter);
    event RewardDistributed(address indexed inviter, uint256 amount, uint256 level);
    event RewardPoolRefilled(uint256 amount);

    // ============ 修饰符 ============
    modifier onlyAdmin() {
        require(msg.sender == admin, "Only admin");
        _;
    }

    // ============ 构造函数 ============
    constructor(
        string memory _name,
        string memory _symbol,
        uint256 _totalSupply,
        uint256 _inviteReward,
        uint256[3] memory _referralLevels,
        uint256 _rewardPoolPercent
    ) {
        admin = msg.sender;
        
        // 设置代币基本信息
        name = _name;
        symbol = _symbol;
        
        // 设置代币总量
        totalSupply = _totalSupply * 10**18;
        balanceOf[admin] = totalSupply;
        
        // 设置邀请奖励
        inviteReward = _inviteReward * 10**18;
        referralLevels = _referralLevels;
        
        // 初始化奖励池：从 admin 余额中划转到合约
        rewardPool = (totalSupply * _rewardPoolPercent) / 100;
        rewardPoolInitial = rewardPool;
        balanceOf[address(this)] = rewardPool;
        balanceOf[admin] -= rewardPool;
        
        // 注册 admin
        isRegistered[admin] = true;
    }

    // ============ 用户注册（邀请制） ============
    function register(address _inviter) external {
        require(!isRegistered[msg.sender], "Already registered");
        require(msg.sender != _inviter, "Cannot invite yourself");

        // 标记注册
        isRegistered[msg.sender] = true;
        inviterOf[msg.sender] = _inviter;
        inviteCount[_inviter]++;

        // 分发邀请奖励
        _distributeRewards(_inviter);

        emit UserRegistered(msg.sender, _inviter);
    }

    // ============ 管理员代注册 ============
    function adminRegister(address _user, address _inviter) external onlyAdmin {
        require(!isRegistered[_user], "Already registered");

        isRegistered[_user] = true;
        inviterOf[_user] = _inviter;
        inviteCount[_inviter]++;

        _distributeRewards(_inviter);

        emit UserRegistered(_user, _inviter);
    }

    // ============ 奖励分配核心逻辑 ============
    function _distributeRewards(address _inviter) internal {
        require(rewardPool >= inviteReward, "Insufficient reward pool");

        address currentInviter = _inviter;
        uint256 totalDistributed = 0;

        // 三级推荐奖励分配
        for (uint256 level = 0; level < 3; level++) {
            uint256 rewardShare = (inviteReward * referralLevels[level]) / 100;

            if (currentInviter != address(0) && rewardShare > 0) {
                // 从奖励池转账到邀请人钱包
                balanceOf[address(this)] -= rewardShare;
                balanceOf[currentInviter] += rewardShare;
                totalRewardsEarned[currentInviter] += rewardShare;
                totalDistributed += rewardShare;

                emit RewardDistributed(currentInviter, rewardShare, level + 1);
            }

            if (currentInviter != address(0)) {
                currentInviter = inviterOf[currentInviter];
            }
        }

        // 未分配完的奖励归管理员
        if (totalDistributed < inviteReward) {
            uint256 remaining = inviteReward - totalDistributed;
            balanceOf[address(this)] -= remaining;
            balanceOf[admin] += remaining;
            totalRewardsEarned[admin] += remaining;
            totalDistributed += remaining;
        }

        rewardPool -= totalDistributed;
    }

    // ============ 充值奖励池 ============
    function refillRewardPool(uint256 _amount) external onlyAdmin {
        uint256 amount = _amount * 10**18;
        require(balanceOf[admin] >= amount, "Insufficient balance");
        balanceOf[admin] -= amount;
        balanceOf[address(this)] += amount;
        rewardPool += amount;

        emit RewardPoolRefilled(amount);
    }

    // ============ ERC-20 转账 ============
    function transfer(address recipient, uint256 amount) external returns (bool) {
        require(balanceOf[msg.sender] >= amount, "Insufficient balance");
        balanceOf[msg.sender] -= amount;
        balanceOf[recipient] += amount;
        emit Transfer(msg.sender, recipient, amount);
        return true;
    }

    // ============ ERC-20 授权 ============
    function approve(address spender, uint256 amount) external returns (bool) {
        allowance[msg.sender][spender] = amount;
        emit Approval(msg.sender, spender, amount);
        return true;
    }

    // ============ ERC-20 授权转账 ============
    function transferFrom(address sender, address recipient, uint256 amount) external returns (bool) {
        require(allowance[sender][msg.sender] >= amount, "Allowance insufficient");
        require(balanceOf[sender] >= amount, "Insufficient balance");
        allowance[sender][msg.sender] -= amount;
        balanceOf[sender] -= amount;
        balanceOf[recipient] += amount;
        emit Transfer(sender, recipient, amount);
        return true;
    }

    // ============ 查询函数 ============
    function getInviterChain(address _user) external view returns (address[3] memory inviters) {
        address current = _user;
        for (uint256 i = 0; i < 3; i++) {
            current = inviterOf[current];
            if (current == address(0)) break;
            inviters[i] = current;
        }
    }

    function getRewardPoolInfo() external view returns (uint256 poolBalance, uint256 poolInitial, uint256 remainingInvites) {
        poolBalance = rewardPool;
        poolInitial = rewardPoolInitial;
        remainingInvites = inviteReward > 0 ? rewardPool / inviteReward : 0;
    }

    // ============ 管理员配置接口 ============
    function setReferralLevels(uint256[3] memory _referralLevels) external onlyAdmin {
        require(_referralLevels[0] + _referralLevels[1] + _referralLevels[2] <= 100, "Total ratio must <= 100");
        referralLevels = _referralLevels;
    }

    function setInviteReward(uint256 _inviteReward) external onlyAdmin {
        require(_inviteReward > 0, "Invite reward must > 0");
        inviteReward = _inviteReward * 10**18;
    }
}
