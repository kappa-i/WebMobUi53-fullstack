import { ref, shallowRef } from "vue";

const defaultHeaders = {
  'Content-Type': 'application/json',
  'X-Requested-With': 'XmlHttpRequest',
  'Accept': 'application/json',
};

let defaultBaseUrl = '';

export function setDefaultHeaders(headers) {
  Object.assign(defaultHeaders, headers);
}

export function setDefaultBaseUrl(url) {
  if (url[url.length - 1] === '/') url = url.slice(0, -1);
  defaultBaseUrl = url;
}

/**
 * Fetch API composable
 *
 * @param {string} [baseUrl=null] - The base URL for the API
 * if not specified, it will be the default base URL (see above to specify it)
 * @param {object} [additionalHeaders={}] - Additional headers to send with each request
 * if not specified, it will be the default headers (see above to specify them)
 * @returns {Object} The fetch API utilities
 * @property {Function} fetchApi - Function to fetch data
 * @property {Function} fetchApiToRef - Function to fetch data and return it in refs
 */
export function useFetchApi(baseUrl = null, additionalHeaders = {}) {
  if (baseUrl === null) baseUrl = defaultBaseUrl;
  if (baseUrl[baseUrl.length - 1] === '/') baseUrl = baseUrl.slice(0, -1);

  const baseHeaders = {...defaultHeaders, ...additionalHeaders};

  /**
   * Fetch data from an API
   *
   * @param {object} options - The fetch options
   * @param {string} options.url - The URL to fetch (mandatory)
   * @param {object} [options.data=null] - The data to send (if any)
   * @param {string} [options.method=null] - The method to use (GET, POST, PUT, DELETE, etc.)
   * if not specified, it will be GET if data is null, POST otherwise
   * @param {object} [options.headers={}] - The additional headers to send (if any)
   */
  function fetchApi({
    url,
    data = null,
    method = null,
    headers = {},
    timeout = 5000,
  }) {
    if (url == null || typeof url !== 'string') throw new Error('The URL must be a string.');

    url = url[0] === '/' ? url : '/' + url;
    const fullUrl = baseUrl + url;
    const allHeaders = {...baseHeaders, ...headers};
    method = method != null ? method.toUpperCase() : data != null ? 'POST' : 'GET';

    return new Promise((resolve, reject) => {
      const controller = new AbortController();
      const timer = setTimeout(() => controller.abort(), timeout);

      fetch(fullUrl, {
        method,
        headers: allHeaders,
        body: data != null ? JSON.stringify(data) : null,
        signal: controller.signal,
        credentials: 'same-origin',
      })
      .then(response => {
        clearTimeout(timer);

        const responseClone = response.clone(); // Clone the response to use it twice if needed

        return response.json()
          .then(data => {
            if (!response.ok) {
              reject({ status: response.status, statusText: response.statusText, data });
            } else {
              resolve(data);
            }
          })
          .catch(() => {
            return responseClone.text()
              .then(() => {
                reject({
                  status: response.status,
                  statusText: 'Error parsing response body as JSON',
                  data: null,
                });
              })
              .catch(err => {
                reject({
                  status: response.status,
                  statusText: 'Error parsing response body',
                  data: null,
                });
              });
          });
      })
      .catch(err => {
        clearTimeout(timer);
        if (err.name === 'AbortError') {
          reject({ status: 0, statusText: 'Timeout', data: null });
        } else {
          reject({ status: 0, statusText: err.message || 'Network error', data: null });
        }
      });
    });
  }

  /**
   * Fetch data from an API and return it in refs
   *
   * @param {object} options - The fetch options
   * @param {string} options.url - The URL to fetch (mandatory)
   * @param {object} [options.data=null] - The data to send (if any)
   * @param {string} [options.method=null] - The method to use (GET, POST, PUT, DELETE, etc.)
   * if not specified, it will be GET if data is null, POST otherwise
   * @param {object} [options.headers={}] - The additional headers to send (if any)
   * @param {boolean} [options.immediate=true] - Whether to fetch immediately on call
   * if false, the fetch will only be triggered when fetchNow() is called manually
   * @returns {Object} The refs with the data, the error and the loading state
   * @property {Ref} data - The data fetched
   * @property {Ref} error - The error if any
   * @property {Ref} loading - The loading state
   * @property {Function} fetchNow - Function to (re)trigger the fetch manually
   */
  function fetchApiToRef({ immediate = true, ...options }) {
    const data = ref(null);
    const error = shallowRef(null);
    const loading = ref(immediate);

    if (options?.url == null || typeof options?.url !== 'string') {
      error.value = { status: 0, statusText: 'The URL must be a string.', data: null };
      loading.value = false;
      return { data, error, loading };
    }

    function fetchNow() {
      loading.value = true;
      error.value = null;
      fetchApi(options)
        .then(res => {
          data.value = res;
          loading.value = false;
        })
        .catch(err => {
          error.value = err;
          loading.value = false;
        });
    }

    if (immediate) fetchNow();

    return { data, error, loading, fetchNow };
  }

  return { fetchApi, fetchApiToRef };
}
