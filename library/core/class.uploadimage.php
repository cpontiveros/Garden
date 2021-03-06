<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Handles uploading image files.
 */
class Gdn_UploadImage extends Gdn_Upload {

   /**
    * Validates the uploaded image. Returns the temporary name of the uploaded file.
    */
   public function ValidateUpload($InputName) {
      // Make sure that all standard file upload checks are performed.
      $TmpFileName = parent::ValidateUpload($InputName);
      
      // Now perform image-specific checks
      $Size = getimagesize($TmpFileName);
      if ($Size === FALSE)
         throw new Exception(Gdn::Translate('The uploaded file was not an image.'));
      
      return $TmpFileName;
   }
   
   /**
    * Saves the specified image at $Target in the specified format with the
    * specified dimensions (or the existing dimensions if height/width are not
    * provided.
    *
    * @param string The path to the source image. Typically this is the tmp file name returned by $this->ValidateUpload();
    * @param string The full path to where the image should be saved, including image name.
    * @param int An integer value indicating the maximum allowed height of the image (in pixels).
    * @param int An integer value indicating the maximum allowed width of the image (in pixels).
    * @param bool Image proportions will always remain constrained. The Crop parameter is a boolean value indicating if the image should be cropped when one dimension (height or width) goes beyond the constrained proportions.
    * @param string The format in which the output image should be saved. Options are: jpg, png, and gif. Default is jpg.
    * @param int An integer value representing the qualityof the saved image. Ranging from 0 (worst quality, smaller file) to 100 (best quality, biggest file).
    */
   public static function SaveImageAs($Source, $Target, $Height = '', $Width = '', $Crop = FALSE, $OutputType = 'jpg', $ImageQuality = 75) {
      // Make sure type, height & width are properly defined
      $Size = getimagesize($Source);
      list($WidthSource, $HeightSource, $Type) = $Size;
      if ($Height == '' || !is_numeric($Height))
         $Height = $HeightSource;
         
      if ($Width == '' || !is_numeric($Width))
         $Width = $WidthSource;
      
      // Don't resize if the source dimensions are smaller than the target dimensions
      $XCoord = 0;
      $YCoord = 0;
      if ($HeightSource > $Height || $WidthSource > $Width) {
         $HeightDiff = $HeightSource - $Height;
         $WidthDiff = $WidthSource - $Width;
         $AspectRatio = (float) $WidthSource / $HeightSource;
         if ($Crop === FALSE) {
            if ($WidthDiff > $HeightDiff) {
               $Height = round($Width / $AspectRatio);
            } else {
               $Width = round($Height * $AspectRatio);
            }
         } else {
            if ($WidthDiff > $HeightDiff) {
               // Crop the original width down
               $NewWidthSource = round(($Width * $HeightSource) / $Height);
               
               // And set the original x position to the cropped start point
               $XCoord = round(($WidthSource - $NewWidthSource) / 2);
               $WidthSource = $NewWidthSource;
            } else {
               // Crop the original height down
               $NewHeightSource = round(($Height * $WidthSource) / $Width);
               
               // And set the original y position to the cropped start point
               $YCoord = round(($HeightSource - $NewHeightSource) / 2);
               $HeightSource = $NewHeightSource;
            }
         }
      } else {
         // Neither target dimension is larger than the original, so keep the original dimensions.
         $Height = $HeightSource;
         $Width = $WidthSource;
      }

      switch ($Type) {
         case 1:
            $SourceImage = imagecreatefromgif($Source);
            break;
         case 2:
            $SourceImage = imagecreatefromjpeg($Source);
            break;
         case 3:
            $SourceImage = imagecreatefrompng($Source);
            break;
         default:
            throw new Exception(sprintf(Gdn::Translate('You cannot save images of this type (%s).'), $Type));
            break;
      }
      
      $TargetImage = imagecreatetruecolor($Width, $Height);
      imagecopyresampled($TargetImage, $SourceImage, 0, 0, $XCoord, $YCoord, $Width, $Height, $WidthSource, $HeightSource);
      imagedestroy($SourceImage);
      
      if ($OutputType == 'gif')
         imagegif($TargetImage, $Target);
      else if ($OutputType == 'png')
         imagepng($TargetImage, $Target, $ImageQuality);
      else
         imagejpeg($TargetImage, $Target, $ImageQuality);
   }
   
   public function GenerateTargetName($TargetFolder, $Extension = 'jpg') {
      $Name = RandomString(12);
      while (file_exists($TargetFolder . DS . $Name . '.' . $Extension)) {
         $Name = RandomString(12);
      }
      return $TargetFolder . DS . $Name . '.' . $Extension;
   }
}