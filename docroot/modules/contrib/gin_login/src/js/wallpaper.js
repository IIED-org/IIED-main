((Drupal, drupalSettings, once) => {
  Drupal.behaviors.ginLoginWallpaper = {
    attach: function attach(context) {
      once('ginLogin', '.user-form-page__wallpaper', context).forEach(() => {
        // Random wallpaper.
        Drupal.ginLoginWallpaper.randomWallpaper();
      });
    },
  };

  Drupal.ginLoginWallpaper = {
    randomWallpaper: () => {
      const path = drupalSettings.gin_login.path + '/images/wallpapers/';
      const wallpapers = [
        'eberhard-grossgasteiger-uE1fGoWWHsY-unsplash.jpg',
        'eberhard-grossgasteiger-v5qZ8VnPamg-unsplash.jpg',
        'eberhard-grossgasteiger-ULC4CL2MGK8-unsplash.jpg',
        'eberhard-grossgasteiger-mt1sZs4QBcw-unsplash.jpg',
        'claudio-schwarz-mYY3NNP47pU-unsplash.jpg',
        'claudio-schwarz-XmXQ5Ek60pk-unsplash.jpg',
        'micha-sager-lsDJyu_uJhw-unsplash.jpg',
      ];

      const wallpaper = wallpapers[Math.floor(Math.random() * wallpapers.length)];
      let image = new Image();
      image.src = path+wallpaper;
      document.querySelector('.gin-login .user-form-page__wallpaper').appendChild(image);
    },

  };
})(Drupal, drupalSettings, once);
