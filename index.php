<?php
// 1. Gọi file header
require 'includes/header.php'; 
?>

<style>
    /* HERO SECTION (Banner chính) */
    .hero-section {
        background: linear-gradient(rgba(91, 116, 58, 0.9), rgba(91, 116, 58, 0.7)), url('https://congcaphe.com/static/images/home/home-banner.jpg'); /* Giả lập ảnh nền */
        background-size: cover;
        background-position: center;
        color: white;
        padding: 80px 20px;
        text-align: center;
        border-radius: 12px;
        margin-bottom: 40px;
        box-shadow: 0 10px 30px rgba(91, 116, 58, 0.3);
    }
    .hero-section h1 {
        font-size: 3.5rem;
        margin-bottom: 15px;
        color: #fff; /* Chữ trắng */
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    .hero-section p {
        font-size: 1.2rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }
    .cta-button {
        display: inline-block;
        background-color: #fff;
        color: #5B743A;
        padding: 12px 30px;
        font-size: 1.1rem;
        font-weight: bold;
        text-decoration: none;
        border-radius: 50px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .cta-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        background-color: #f0f0f0;
    }

    /* FEATURES SECTION (3 Cột) */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-bottom: 60px;
    }
    .feature-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
    }
    .feature-card:hover {
        transform: translateY(-5px);
    }
    .icon-box {
        font-size: 40px;
        margin-bottom: 20px;
        color: #5B743A;
    }
    .feature-card h3 {
        margin-bottom: 10px;
        color: #333;
    }
    .feature-card p {
        color: #666;
        line-height: 1.6;
    }

    /* ABOUT SECTION */
    .about-section {
        background: white;
        padding: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 40px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }
    .about-content h2 {
        color: #5B743A;
        margin-bottom: 20px;
        font-size: 2rem;
    }
    .about-content p {
        color: #555;
        line-height: 1.8;
        font-size: 1.1rem;
    }
    .about-image {
        flex: 1;
        border-radius: 10px;
        overflow: hidden;
    }
    .about-image img {
        width: 100%;
        height: auto;
        display: block;
    }

    @media (max-width: 768px) {
        .about-section { flex-direction: column; }
    }
</style>

<div class="hero-section">
    <h1>Cộng Cà Phê</h1>
    <p>Lan tỏa hương vị Việt - Hệ thống quản lý chuyên nghiệp</p>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php $link = ($_SESSION['role'] == 'admin') ? 'admin/product_list.php' : 'pos/pos.php'; ?>
        <a href="<?php echo $link; ?>" class="cta-button">Vào Trang Quản Lý</a>
    <?php else: ?>
        <a href="login.php" class="cta-button">Đăng Nhập Ngay</a>
    <?php endif; ?>
</div>

<div class="features-grid">
    <div class="feature-card">
        <div class="icon-box">☕</div>
        <h3>Hương Vị Đậm Đà</h3>
        <p>Tuyển chọn những hạt cà phê Robusta và Arabica tốt nhất từ vùng đất Tây Nguyên đầy nắng gió.</p>
    </div>
    <div class="feature-card">
        <div class="icon-box">🍃</div>
        <h3>Không Gian Xưa</h3>
        <p>Tái hiện không gian bao cấp đầy hoài niệm, mang lại cảm giác bình yên giữa lòng phố thị.</p>
    </div>
    <div class="feature-card">
        <div class="icon-box">🚀</div>
        <h3>Quản Lý Hiện Đại</h3>
        <p>Hệ thống phần mềm tối ưu, giúp quản lý kho, đơn hàng và nhân sự một cách chính xác nhất.</p>
    </div>
</div>

<div class="about-section">
    <div class="about-content">
        <h2>Câu Chuyện Của Cộng</h2>
        <p>
            Cộng Cà Phê ra đời năm 2007, khởi nguồn là một tiệm giải khát nhỏ trên con phố Triệu Việt Vương lịch sử tại Hà Nội. 
            Cộng được truyền cảm hứng từ những vật dụng, không gian thời bao cấp xã hội chủ nghĩa Việt Nam.
            <br><br>
            Chúng tôi nỗ lực khơi dậy trí tưởng tượng và mang đến cho khách hàng những trải nghiệm cảm xúc khác biệt về Việt Nam.
        </p>
    </div>
    <div class="about-image">
        <div style="background-color: #5B743A; width: 100%; height: 250px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
            [Ảnh Quán Cà Phê]
        </div>
    </div>
</div>

<?php
// 4. Gọi file footer
require 'includes/footer.php'; 
?>