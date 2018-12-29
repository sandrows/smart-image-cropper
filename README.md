## Smart Image Cropper
The core purpose of this script is to simplify the process of cropping an image to different desired images with different resolutions as well. It allows an image to have "Focal Areas", which basically is a definition of the important parts of the image.

When an image is cropped, the script will intelligently determine where to crop according to the selected areas so as to produce an output that is relevant.

Mainly, there are two ways to tell the script how to do the crop. The first one is to use **compass directions** ('n' for North, 'nw' for North West, etc...) and the second one is to let the script place the pivot smartly according to the weight of each area; 'cm' for **Centre of Mass**.

An extra feature is the ability to add text to the image using an array setting all options for the text to be added:
```php
$text = [
  'family' => 'DejaVu Sans Mono',
  'fill' => 'white',
  'pointsize' => '40',
  'weight' => '800',
  'style' => 'Italic',
  'text' => 'Text 2\nLine 2',
  'gravity' => 'northeast'
];
```  

### Usage
As this code depends on [Image Magick](http://www.imagemagick.org/), it is ready to run from any linux-based machine with the convert command available. To check if Image Magick is installed, run this from terminal:
`which convert`, it shall point to the location of the package.

For a demonstration of how it works; I have prepared some demo images with their focus areas included in the JSON file and contrasted more in the images. [Download them here](https://1drv.ms/u/s!ArHIW2lUlBjgkRbAr797W0XN514g) and extract the archive at the root. A new directory "img" shall be created containing the images.

And thus you can run the script from the terminal:
```text
php focuspoint.php
```
