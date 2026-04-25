/**
 * ==========================================
 * ส่วนที่ 3 & 4: ระบบ Welcome Slider (แบนเนอร์หลัก)
 * ==========================================
 */

let currentIndex = 0; 
let autoSlideInterval; 
let touchStartX = 0; 
let touchEndX = 0; 

function handleSwipe() {
    const swipeDistance = touchEndX - touchStartX;
    const minSwipeDistance = 50; 

    if (swipeDistance < -minSwipeDistance) {
        moveSlide('main', 1);
    } else if (swipeDistance > minSwipeDistance) {
        moveSlide('main', -1);
    }
}

function moveSlide(sliderName, direction) {
    const track = document.getElementById('main-track');
    const totalSlides = document.querySelectorAll('#main-track .slide').length; 

    if (!track || totalSlides === 0) return; 

    currentIndex += direction; 

    if (currentIndex >= totalSlides) {
        currentIndex = 0; 
    } else if (currentIndex < 0) {
        currentIndex = totalSlides - 1; 
    }

    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    
    clearInterval(autoSlideInterval);
    startAutoSlide();
}

function startAutoSlide() {
    clearInterval(autoSlideInterval);
    
    autoSlideInterval = setInterval(() => {
        const track = document.getElementById('main-track');
        const totalSlides = document.querySelectorAll('#main-track .slide').length;
        
        if (!track || totalSlides === 0) return;

        currentIndex += 1;
        if (currentIndex >= totalSlides) {
            currentIndex = 0;
        }
        
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
    }, 3000); 
}

/**
 * ==========================================
 * ส่วนที่ 5: ระบบข่าวประชาสัมพันธ์ (News Slider & Pop-up)
 * ==========================================
 */

// --- 5.1 ระบบเลื่อนการ์ดข่าว (News Navigation) ---
document.addEventListener("DOMContentLoaded", function() {
    const newsContainer = document.getElementById('newsContainer');
    const prevNewsBtn = document.querySelector('.prev-news');
    const nextNewsBtn = document.querySelector('.next-news');

    if (newsContainer && nextNewsBtn && prevNewsBtn) {
        nextNewsBtn.addEventListener('click', () => {
            const cardWidth = document.querySelector('.news-card').offsetWidth + 20; 
            newsContainer.scrollBy({ left: cardWidth, behavior: 'smooth' });
        });

        prevNewsBtn.addEventListener('click', () => {
            const cardWidth = document.querySelector('.news-card').offsetWidth + 20;
            newsContainer.scrollBy({ left: -cardWidth, behavior: 'smooth' });
        });
    }

    // --- 5.2 ระบบ Pop-up รายละเอียดข่าว (News Modal) ---
    const modal = document.getElementById("newsModal");
    const closeBtn = document.querySelector(".close-modal");
    
    const modalTitle = document.getElementById("modalTitle");
    const modalImage = document.getElementById("modalImage");
    const modalTextDetails = document.getElementById("modalTextDetails");
    const modalTextBody = document.getElementById("modalTextBody");      
    const modalTextAuthor = document.getElementById("modalTextAuthor");  

    const newsCards = document.querySelectorAll(".news-card");

    newsCards.forEach(card => {
        card.addEventListener("click", function() {
            const imgSrc = this.querySelector(".news-image img").src;
            const title = this.querySelector("h3").innerText;
            const desc = this.querySelector(".news-desc").innerHTML;
            
            const paragraphs = this.querySelectorAll("p");
            let detailsHtml = "";
            for (let i = 1; i < 3 && i < paragraphs.length; i++) { 
                detailsHtml += `<p>${paragraphs[i].innerHTML}</p>`;
            }

            modalTitle.innerText = title;
            modalImage.src = imgSrc;
            modalTextDetails.innerHTML = detailsHtml; 
            modalTextBody.innerHTML = desc;         

            modalTextAuthor.innerHTML = `<p><strong>แหล่งข้อมูล:</strong> มหาวิทยาลัยศรีนครินทรวิโรฒ</p><p><strong>ผู้เขียน/ภาพ:</strong> -</p>`;

            modal.style.display = "flex";
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener("click", function() {
            modal.style.display = "none";
        });
    }

    window.addEventListener("click", function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
});

/**
 * ==========================================
 * ส่วนที่ 6: ระบบสไลด์บุคลากร (Staff Slider)
 * ==========================================
 */

const currentSlideIndex = {
    staff: 0
};

function moveSlide(sliderId, step) { 
    const track = document.getElementById(sliderId + '-track');
    if (!track) return;

    const slides = track.querySelectorAll('.slide');
    if (slides.length === 0) return;

    const slideWidth = slides[0].offsetWidth;
    const visibleSlides = Math.round(track.parentElement.offsetWidth / slideWidth);
    const maxIndex = slides.length - visibleSlides;

    currentSlideIndex[sliderId] += step;

    if (currentSlideIndex[sliderId] < 0) {
        currentSlideIndex[sliderId] = 0; 
    } else if (currentSlideIndex[sliderId] > maxIndex) {
        currentSlideIndex[sliderId] = maxIndex; 
    }

    track.style.transform = `translateX(-${currentSlideIndex[sliderId] * slideWidth}px)`;
}

/**
 * ==========================================
 * ส่วนที่ 7: ระบบปฏิทินกิจกรรม (Calendar)
 * ==========================================
 */

const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
let currentDate = new Date();

const events = {
    "2026-4-10": { type: "mark-blue", title: "วัน Showcase โปรเจกต์!" },
    "2026-4-13": { type: "mark-gray", title: "วันสงกรานต์" },
    "2026-8-8":  { type: "mark-blue", title: "ปฐมนิเทศ" },
    "2026-8-15": { type: "mark-red", title: "สอบกลางภาค" },
    "2026-10-25":{ type: "mark-red", title: "สอบปลายภาค" }
};

function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth(); 
    
    const monthYearElem = document.getElementById("month-year");
    if (monthYearElem) monthYearElem.innerText = `${monthNames[month]} ${year}`;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    const daysContainer = document.getElementById("calendar-days");
    if (!daysContainer) return;
    
    daysContainer.innerHTML = ""; 
    
    for (let i = 0; i < firstDay; i++) {
        daysContainer.innerHTML += `<div></div>`;
    }
    
    for (let i = 1; i <= daysInMonth; i++) {
        const dateString = `${year}-${month + 1}-${i}`;
        let eventClass = "";
        let titleAttr = "";
        
        if (events[dateString]) {
            eventClass = events[dateString].type;
            titleAttr = `title="${events[dateString].title}"`;
        }
        
        daysContainer.innerHTML += `<div class="${eventClass}" ${titleAttr}>${i}</div>`;
    }
}

function changeMonth(direction) {
    currentDate.setMonth(currentDate.getMonth() + direction);
    renderCalendar(currentDate);
}

// เรียกใช้งานปฏิทินทันที
renderCalendar(currentDate);

/**
 * ==========================================
 * ส่วนที่ 8: ระบบแจ้งเตือนคุ๊กกี้ (Cookie Banner)
 * ==========================================
 */

document.addEventListener("DOMContentLoaded", function() {
    const cookieBanner = document.getElementById("cookie-banner");
    const acceptBtn = document.getElementById("accept-cookie");
    const declineBtn = document.getElementById("decline-cookie");

    if (cookieBanner) {
        if (!localStorage.getItem("swu_cookie_consent")) {
            setTimeout(() => {
                cookieBanner.classList.add("show");
            }, 1000);
        }

        acceptBtn.addEventListener("click", function() {
            localStorage.setItem("swu_cookie_consent", "accepted"); 
            cookieBanner.classList.remove("show"); 
        });

        declineBtn.addEventListener("click", function() {
            localStorage.setItem("swu_cookie_consent", "declined"); 
            cookieBanner.classList.remove("show"); 
        });
    }
});

/**
 * ==========================================
 * การตั้งค่า Event Listeners สำหรับ Welcome Slider
 * ==========================================
 */

document.addEventListener("DOMContentLoaded", function() {
    const track = document.getElementById('main-track');
    const slides = document.querySelectorAll('#main-track .slide');
    
    if (track && slides.length > 0) {
        startAutoSlide();

        // Touch Events สำหรับมือถือ
        track.addEventListener('touchstart', function(event) {
            touchStartX = event.changedTouches[0].screenX;
        }, { passive: true });

        track.addEventListener('touchend', function(event) {
            touchEndX = event.changedTouches[0].screenX;
            handleSwipe(); 
        }, { passive: true });

        // Mouse Events สำหรับคอมพิวเตอร์
        track.addEventListener('mousedown', function(event) {
            touchStartX = event.screenX;
        });

        track.addEventListener('mouseup', function(event) {
            touchEndX = event.screenX;
            handleSwipe(); 
        });

        track.addEventListener('dragstart', function(event) {
            event.preventDefault();
        });
    }
});