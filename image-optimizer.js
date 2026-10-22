// Resize new specimen photos in the browser so shared hosting without GD
// still receives a consistently sized, compressed image.
(() => {
  const form = document.querySelector('form.specimen-form[enctype="multipart/form-data"]');
  const input = form?.querySelector('input[type="file"][name="image"]');
  if (!form || !input) return;

  let submitting = false;
  form.addEventListener('submit', async (event) => {
    if (submitting) return;
    const original = input.files?.[0];
    if (!original || original.type === 'image/gif' || !/^image\/(jpeg|png|webp)$/.test(original.type)) return;

    event.preventDefault();
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Preparing image…';
    }

    try {
      const bitmap = await createImageBitmap(original);
      const targetRatio = 4 / 3;
      const sourceRatio = bitmap.width / bitmap.height;
      let cropWidth = bitmap.width;
      let cropHeight = bitmap.height;
      let cropX = 0;
      let cropY = 0;
      if (sourceRatio > targetRatio) {
        cropWidth = Math.round(bitmap.height * targetRatio);
        cropX = Math.floor((bitmap.width - cropWidth) / 2);
      } else {
        cropHeight = Math.round(bitmap.width / targetRatio);
        cropY = Math.floor((bitmap.height - cropHeight) / 2);
      }

      const scale = Math.min(1, 1200 / cropWidth, 900 / cropHeight);
      const canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(cropWidth * scale));
      canvas.height = Math.max(1, Math.round(cropHeight * scale));
      const context = canvas.getContext('2d');
      context.fillStyle = '#ffffff';
      context.fillRect(0, 0, canvas.width, canvas.height);
      context.drawImage(bitmap, cropX, cropY, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height);
      bitmap.close();

      const optimized = await new Promise((resolve, reject) => {
        canvas.toBlob((blob) => blob ? resolve(blob) : reject(new Error('Image compression failed')), 'image/jpeg', 0.82);
      });
      const optimizedFile = new File([optimized], original.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
      const transfer = new DataTransfer();
      transfer.items.add(optimizedFile);
      input.files = transfer.files;
    } catch (error) {
      // Keep the selected original if this browser cannot resize it.
    }

    submitting = true;
    form.submit();
  });
})();
