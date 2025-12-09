<?php
// 1. Gọi file header
require 'includes/header.php'; 
?>

<style>
    /* Reset lại padding content */
    main.content {
        padding: 0; 
        margin-top: 70px; 
        max-width: 100%;
    }

    .home-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* HERO SECTION (Banner) */
    .hero-section {
        /* Ảnh nền Banner */
        background: linear-gradient(rgba(50, 70, 30, 0.8), rgba(50, 70, 30, 0.6)), url('https://cong-news.appwifi.com/wp-content/uploads/2023/05/IMG_4045.jpg');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 120px 20px;
        text-align: center;
        margin-bottom: 0; 
    }
    .hero-section h1 {
        font-size: 3.5rem;
        margin-bottom: 15px;
        text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        letter-spacing: 2px;
    }
    .hero-section p {
        font-size: 1.3rem;
        margin-bottom: 35px;
        font-weight: 300;
    }
    .cta-button {
        display: inline-block;
        background-color: #5B743A; 
        color: white;
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: bold;
        text-decoration: none;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        border: 2px solid white;
    }
    .cta-button:hover {
        transform: translateY(-3px);
        background-color: white;
        color: #5B743A;
    }

    /* FEATURES SECTION */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
        margin: 60px 0;
    }
    .feature-card {
        background: white;
        padding: 40px 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
        border: 1px solid #eee;
    }
    .feature-card:hover {
        transform: translateY(-10px);
        border-color: #5B743A;
    }
    .icon-box {
        font-size: 50px;
        margin-bottom: 20px;
        color: #5B743A;
    }
    .feature-card h3 { margin-bottom: 15px; color: #333; font-size: 1.4rem;}
    .feature-card p { color: #666; line-height: 1.6; }

    /* ABOUT SECTION - ĐÃ CHỈNH SỬA KÍCH THƯỚC ẢNH */
    .about-section {
        display: flex;
        align-items: center;
        gap: 60px;
        padding: 40px 0;
    }
    .about-content { 
        flex: 3; /* Phần chữ chiếm 3 phần */
    }
    .about-content h2 {
        color: #5B743A;
        margin-bottom: 25px;
        font-size: 2.2rem;
        position: relative;
        display: inline-block;
    }
    .about-content h2::after {
        content: '';
        display: block;
        width: 60px;
        height: 4px;
        background: #5B743A;
        margin-top: 10px;
    }
    .about-content p {
        color: #555;
        line-height: 1.8;
        font-size: 1.1rem;
        text-align: justify;
    }
    
    /* SỬA: Giới hạn khung ảnh */
    .about-image {
        flex: 2; /* Phần ảnh chiếm 2 phần (nhỏ hơn chữ) */
        max-width: 450px; /* Giới hạn chiều rộng tối đa */
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 20px 20px 0px #e9ecef; 
    }
    /* SỬA: Giới hạn chiều cao ảnh và cắt ảnh tự động */
    .about-image img {
        width: 100%;
        height: 350px; /* Cố định chiều cao */
        object-fit: cover; /* Cắt ảnh vừa khung mà không bị méo */
        display: block;
        transition: transform 0.5s ease;
    }
    .about-image:hover img {
        transform: scale(1.05); 
    }
    
    @media (max-width: 768px) {
        .about-section { flex-direction: column; }
        .hero-section { padding: 60px 20px; }
        .hero-section h1 { font-size: 2.5rem; }
        .about-image { max-width: 100%; width: 100%; } /* Trên mobile thì cho full width */
    }
</style>

<div class="hero-section">
    <h1>Cộng Cà Phê</h1>
    <p>Lan tỏa hương vị Việt - Hệ thống quản lý chuyên nghiệp</p>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php $link = ($_SESSION['role'] == 'admin') ? 'pos/pos.php' : 'pos/pos.php'; ?>
        <a href="<?php echo $link; ?>" class="cta-button">Vào Trang Bán Hàng</a>
    <?php else: ?>
        <a href="login.php" class="cta-button">Đăng Nhập Ngay</a>
    <?php endif; ?>
</div>

<div class="home-wrapper">
    
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
            <img src="https://cong-news.appwifi.com/wp-content/uploads/2023/05/IMG_4045.jpg" alt="Không gian quán Cộng">
        </div>
        
    </div>

</div>

<?php
// 4. Gọi file footer
require 'includes/footer.php'; 
?>