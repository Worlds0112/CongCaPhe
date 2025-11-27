</main>

    <style>
        .site-footer {
            background-color: #2c2c2c; /* Màu nền tối */
            color: #ecf0f1;
            padding: 40px 0 0 0;
            margin-top: auto; /* Giúp footer luôn nằm dưới cùng */
            font-size: 14px;
        }
        .footer-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .footer-col {
            flex: 1;
            min-width: 250px;
            margin-bottom: 20px;
            padding-right: 20px;
        }
        .footer-col h3 {
            color: #5B743A; /* Màu xanh thương hiệu */
            font-size: 18px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #5B743A;
            display: inline-block;
            padding-bottom: 5px;
        }
        .footer-col p {
            line-height: 1.6;
            color: #bbb;
            margin-bottom: 10px;
        }
        .footer-col ul {
            list-style: none;
            padding: 0;
        }
        .footer-col ul li {
            margin-bottom: 10px;
        }
        .footer-col ul li a {
            color: #bbb;
            text-decoration: none;
            transition: color 0.3s;
        }
        .footer-col ul li a:hover {
            color: #5B743A;
            padding-left: 5px;
        }
        
        /* Phần bản quyền dưới cùng */
        .footer-bottom {
            background-color: #1a1a1a;
            text-align: center;
            padding: 15px;
            margin-top: 20px;
            color: #777;
            font-size: 13px;
        }
        
        /* Responsive cho điện thoại */
        @media (max-width: 768px) {
            .footer-container {
                flex-direction: column;
            }
            .footer-col {
                margin-bottom: 30px;
            }
        }
    </style>

    <footer class="site-footer">
        <div class="footer-container">
            
            <div class="footer-col">
                <h3>Cộng Cà Phê</h3>
                <p>
                    Lan tỏa hương vị Việt Nam. Chúng tôi mang đến không gian hoài niệm và những ly cà phê đậm đà bản sắc dân tộc.
                </p>
                <p>
                    <strong>Mã số thuế:</strong> 0123456789<br>
                    <strong>Giấy phép KD:</strong> Cấp ngày 01/01/2007
                </p>
            </div>

            <div class="footer-col">
                <h3>Liên Hệ</h3>
                <p>📍 <strong>Địa chỉ:</strong> 123 Đường Thanh Niên, Ba Đình, Hà Nội</p>
                <p>📞 <strong>Hotline:</strong> 0912 345 678</p>
                <p>📧 <strong>Email:</strong> info@congcaphe.com</p>
            </div>

            <div class="footer-col">
                <h3>Giờ Mở Cửa</h3>
                <ul style="color: #bbb;">
                    <li>Thứ 2 - Thứ 6: 07:00 - 22:00</li>
                    <li>Thứ 7 - Chủ Nhật: 07:00 - 23:00</li>
                </ul>
                
                <h3 style="margin-top: 15px; font-size: 16px;">Theo dõi chúng tôi</h3>
                <div>
                    <a href="#" style="color: white; margin-right: 10px; font-size: 20px;">Facebook</a>
                    <a href="#" style="color: white; margin-right: 10px; font-size: 20px;">Instagram</a>
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            &copy; 2025 Cộng Cà Phê. Hệ thống quản lý được xây dựng bởi Vũ Thành An, Vũ Công Danh, Lê Văn Huấn. All rights reserved.
        </div>
    </footer>

</body>
</html>