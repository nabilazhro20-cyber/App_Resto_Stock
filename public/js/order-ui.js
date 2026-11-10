window.OrderUI = (() => {
  async function requestJson(url, options = {}) {
    const headers = { Accept: 'application/json', ...(options.headers || {}) };
    let response;
    try {
      response = await fetch(url, { ...options, headers });
    } catch (err) {
      throw new Error('Koneksi ke server gagal. Periksa jaringan dan server lokal.');
    }
 
    const body = await response.text();
    let data = null;
    try {
      data = body ? JSON.parse(body) : null;
    } catch (err) {
      throw new Error(`Server mengembalikan respons bukan JSON (HTTP ${response.status}).`);
    }
    if (!response.ok) {
      const error = new Error(data?.message || `Permintaan gagal (HTTP ${response.status}).`);
      error.status = response.status;
      error.fields = data?.errors || {};
      throw error;
    }
    return data;
  }
 
  const rupiah = (value) => new Intl.NumberFormat('id-ID').format(Number(value) || 0);
  function pesanError(error) {
    const fields = Object.values(error?.fields || {});
    return fields.length ? `${error.message} ${fields[0][0]}` : (error?.message || 'Terjadi kesalahan.');
  }
  return { requestJson, rupiah, pesanError };
})();
