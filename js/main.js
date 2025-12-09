(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);
    
    
    // Initiate the wowjs
    new WOW().init();


    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 45) {
            $('.navbar').addClass('sticky-top shadow-sm');
        } else {
            $('.navbar').removeClass('sticky-top shadow-sm');
        }
    });


    // Hero Header carousel
    $(".header-carousel").owlCarousel({
        animateOut: 'slideOutDown',
        items: 1,
        autoplay: true,
        smartSpeed: 1000,
        dots: false,
        loop: true,
        nav : true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
    });


    // International carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        items: 1,
        smartSpeed: 1500,
        dots: true,
        loop: true,
        margin: 25,
        nav : true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ]
    });


    // Modal Video
    $(document).ready(function () {
        var $videoSrc;
        $('.btn-play').click(function () {
            $videoSrc = $(this).data("src");
        });
        console.log($videoSrc);

        $('#videoModal').on('shown.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc + "?autoplay=1&amp;modestbranding=1&amp;showinfo=0");
        })

        $('#videoModal').on('hide.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc);
        })
    });


    // testimonial carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        center: true,
        dots: true,
        loop: true,
        margin: 25,
        nav : true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0:{
                items:1
            },
            576:{
                items:1
            },
            768:{
                items:1
            },
            992:{
                items:1
            },
            1200:{
                items:1
            }
        }
    });

    
    
   // Back to top button
   $(window).scroll(function () {
    if ($(this).scrollTop() > 300) {
        $('.back-to-top').fadeIn('slow');
    } else {
        $('.back-to-top').fadeOut('slow');
    }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


})(jQuery);
document.addEventListener("DOMContentLoaded", function () {
  const stars = document.querySelectorAll("#rating-stars .star");
  const thankYou = document.getElementById("thank-you");
  const form = document.getElementById("feedback-form");
  let selectedRating = 0;

  // Star selection logic
  stars.forEach(star => {
    star.addEventListener("click", function () {
      selectedRating = this.getAttribute("data-value");

      stars.forEach(s => s.classList.remove("selected"));
      for (let i = 0; i < selectedRating; i++) {
        stars[i].classList.add("selected");
      }
    });
  });

  // Submit feedback
  form.addEventListener("submit", function (e) {
    e.preventDefault();

    if (selectedRating === 0) {
      alert("Please select a star rating.");
      return;
    }

    // You can later send this data to backend (PHP/DB)
    thankYou.style.display = "block";
    form.reset();
    stars.forEach(s => s.classList.remove("selected"));
  });
});

// CHAT WITH SPECIALIST
document.addEventListener("DOMContentLoaded", () => {
  const socket = io("http://localhost:3000"); // connect to backend
  const chatBox = document.getElementById("chatBox");
  const chatInput = document.getElementById("chatInput");
  const sendBtn = document.getElementById("sendBtn");

  // Send message
  sendBtn.addEventListener("click", () => {
    const msg = chatInput.value.trim();
    if (msg) {
      socket.emit("chatMessage", msg);
      appendMessage(msg, "user");
      chatInput.value = "";
    }
  });

  chatInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendBtn.click();
  });

  // Receive reply
  socket.on("chatMessage", (data) => {
    if (data.sender === "specialist") {
      appendMessage(data.msg, "specialist");
    }
  });

  function appendMessage(msg, type) {
    const p = document.createElement("p");
    p.innerText = msg;
    p.classList.add(type === "user" ? "user-msg" : "specialist-msg");
    chatBox.appendChild(p);
    chatBox.scrollTop = chatBox.scrollHeight;
  }
});
