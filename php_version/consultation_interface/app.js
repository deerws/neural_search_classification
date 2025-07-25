new Vue({
    el: '#app',
    data: {
      filtroPrograma: '',
      programas: [],  // Preenchido via API
      dados: [],      // Dados filtrados
      svg: null       // Referência do gráfico D3.js
    },
    mounted() {
      // Carrega programas disponíveis ao iniciar
      this.carregarProgramas();
      // Inicializa o gráfico (vazio)
      this.inicializarGrafico();
    },
    methods: {
      carregarProgramas() {
        axios.get('api/programas.php')
          .then(response => {
            this.programas = response.data;
          });
      },
      carregarDados() {
        axios.get('api/dados.php', { params: { programa: this.filtroPrograma } })
          .then(response => {
            this.dados = response.data;
            this.atualizarGrafico(this.dados); // Atualiza o gráfico
          });
      },
      inicializarGrafico() {
        // Configuração inicial do gráfico de corda com D3.js
        this.svg = d3.select("#grafico-corda")
          .append("svg")
          .attr("width", "100%")
          .attr("height", "100%");
      },
      atualizarGrafico(dados) {
        // Configurações do gráfico
        const width = 800, height = 800;
        const svg = d3.select("#grafico-corda").html("")
          .append("svg")
          .attr("width", width)
          .attr("height", height);
      
        // Lógica do gráfico de corda aqui (exemplo simplificado)
        const chords = d3.chord()(dados); // Você precisará formatar seus dados para D3.js
      
        svg.append("g")
          .selectAll("path")
          .data(chords)
          .enter()
          .append("path")
          .attr("d", d3.ribbon().radius(200))
          .attr("fill", "#4CAF50");
      }
    }
  });