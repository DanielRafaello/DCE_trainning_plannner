import { defineConfig } from 'orval'

export default defineConfig({
  api: {
    input: {
      target: './openapi/openapi.yaml'   // path to your OpenAPI file
    },
    output: {
      mode: 'tags-split',           // splits files by tags (controllers)
      target: './src/services/api', // where files are generated
      client: 'axios',              // uses Axios
      override: {
        mutator: {
          path: './src/services/httpClient.ts', // your Axios instance
          name: 'httpClient'
        }
      }
    }
  }
})
