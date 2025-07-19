const parseClassification = (str) => {
  if (!str || typeof str !== 'string') return null;
  const match = str.match(/^([\dA.]+)\.(.*?)\s+\(([\d.]+)\)$/);
  return match ? { code: match[1], description: match[2].trim(), score: parseFloat(match[3]) } : null;
};

// Estrutura hierárquica de domínios/subdomínios
const subdomains = {
  ACARE: {
    '96': {
      name: 'Aerodinâmica e Física de Voo',
      subdomains: [
        { code: '96.1', description: 'Desempenho Aerodinâmico' },
        { code: '96.2', description: 'Dinâmica de Voo' },
        { code: '96.3', description: 'Controle de Fluxo' }
      ]
    },
    '97': {
      name: 'Estruturas de Aeronaves',
      subdomains: [
        { code: '97.1', description: 'Projeto Estrutural' },
        { code: '97.2', description: 'Tecnologia de Materiais' },
        { code: '97.3', description: 'Manufatura Avançada' }
      ]
    }
  },
  NASA: {
    'A1': {
      name: 'Pesquisa Aeronáutica',
      subdomains: [
        { code: 'A1.1', description: 'Veículos Aéreos Avançados' },
        { code: 'A1.2', description: 'Operações no Espaço Aéreo' },
        { code: 'A1.3', description: 'Sistemas de Propulsão' }
      ]
    },
    'A2': {
      name: 'Tecnologia Espacial',
      subdomains: [
        { code: 'A2.1', description: 'Sistemas Autônomos' },
        { code: 'A2.2', description: 'Voo Espacial Humano' },
        { code: 'A2.3', description: 'Materiais Aeroespaciais' }
      ]
    }
  }
};

// Componente de Instruções
const InstructionPanel = () => {
  return React.createElement(
    'div',
    { className: 'instruction-panel p-4 rounded-lg mb-6' },
    React.createElement('h3', { className: 'text-lg font-bold text-blue-400 mb-2' }, '📌 Como Usar o Sistema:'),
    React.createElement(
      'div',
      { className: 'grid grid-cols-1 md:grid-cols-3 gap-4 text-sm' },
      React.createElement(
        'div',
        { className: 'bg-gray-800 p-3 rounded-lg' },
        React.createElement('h4', { className: 'font-semibold text-blue-300 mb-2' }, '1. Informações Básicas'),
        React.createElement('ul', { className: 'list-disc list-inside space-y-1 text-gray-300' },
          React.createElement('li', null, 'Digite seu nome no campo indicado'),
          React.createElement('li', null, 'Selecione um programa para filtrar (opcional)')
        )
      ),
      React.createElement(
        'div',
        { className: 'bg-gray-800 p-3 rounded-lg' },
        React.createElement('h4', { className: 'font-semibold text-blue-300 mb-2' }, '2. Classificação'),
        React.createElement('ul', { className: 'list-disc list-inside space-y-1 text-gray-300' },
          React.createElement('li', null, 'Analise as três classificações sugeridas para ACARE e NASA'),
          React.createElement('li', null, 'Aprove ou rejeite cada subdomínio'),
          React.createElement('li', null, 'Adicione novos subdomínios se necessário')
        )
      ),
      React.createElement(
        'div',
        { className: 'bg-gray-800 p-3 rounded-lg' },
        React.createElement('h4', { className: 'font-semibold text-blue-300 mb-2' }, '3. Finalização'),
        React.createElement('ul', { className: 'list-disc list-inside space-y-1 text-gray-300' },
          React.createElement('li', null, 'Adicione comentários se necessário'),
          React.createElement('li', null, 'Baixe o CSV com seus resultados'),
          React.createElement('li', null, 'Consulte os manuais para referência')
        )
      )
    )
  );
};

// Modal de Feedback
const FeedbackModal = ({ isOpen, onClose, feedback, onSubmit, rowIndex }) => {
  const [newFeedback, setNewFeedback] = React.useState('');

  if (!isOpen) return null;

  return React.createElement(
    'div',
    { className: 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50' },
    React.createElement(
      'div',
      { className: 'bg-gray-800 p-6 rounded-lg w-96' },
      React.createElement('h2', { className: 'text-xl font-bold text-blue-400 mb-4' }, `Feedback para Linha #${rowIndex + 1}`),
      React.createElement(
        'div',
        { className: 'mb-4 max-h-40 overflow-y-auto' },
        feedback.length > 0
          ? feedback.map((fb, i) =>
              React.createElement(
                'p',
                { key: i, className: 'text-gray-300 border-b border-gray-700 py-2' },
                fb
              )
            )
          : React.createElement('p', { className: 'text-gray-500' }, 'Sem feedback ainda.')
      ),
      React.createElement('textarea', {
        className: 'w-full p-2 bg-gray-700 text-white border border-gray-600 rounded mb-4',
        placeholder: 'Adicione seu feedback...',
        value: newFeedback,
        onChange: (e) => setNewFeedback(e.target.value)
      }),
      React.createElement(
        'div',
        { className: 'flex justify-end gap-2' },
        React.createElement(
          'button',
          {
            className: 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 active:scale-95 active:shadow-inner transition-transform',
            onClick: () => {
              if (newFeedback.trim()) onSubmit(newFeedback);
              setNewFeedback('');
              onClose();
            }
          },
          'Enviar'
        ),
        React.createElement(
          'button',
          {
            className: 'bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 active:scale-95 active:shadow-inner transition-transform',
            onClick: onClose
          },
          'Fechar'
        )
      )
    )
  );
};

// Modal de Subdomínio com Hierarquia
const SubdomainModal = ({ isOpen, onClose, onSubmit, rowData }) => {
  const [selectedSubdomain, setSelectedSubdomain] = React.useState('');
  const [selectedProgram, setSelectedProgram] = React.useState('ACARE');

  if (!isOpen) return null;

  const renderDomainGroups = () => {
    if (!subdomains[selectedProgram]) return null;

    return Object.entries(subdomains[selectedProgram]).map(([domainCode, domainData]) => {
      return React.createElement(
        'div',
        { key: domainCode, className: 'domain-group mb-3' },
        React.createElement(
          'div',
          { className: 'font-bold text-blue-300 mb-1' },
          domainCode + ' - ' + domainData.name
        ),
        // Subdomínios existentes
        rowData[selectedProgram.toLowerCase()] && 
        rowData[selectedProgram.toLowerCase()].filter(sd => sd && sd.code && sd.code.startsWith(domainCode)).map((sd, i) => 
          React.createElement(
            'div',
            { 
              key: 'existing-' + i,
              className: 'subdomain-item cursor-pointer p-1 hover:bg-gray-700 rounded',
              onClick: () => setSelectedSubdomain(selectedProgram + ':' + sd.code)
            },
            React.createElement(
              'span',
              { className: selectedSubdomain === (selectedProgram + ':' + sd.code) ? 'text-green-400' : 'text-gray-300' },
              '✓ ' + sd.code + ' - ' + sd.description
            )
          )
        ),
        // Subdomínios padrão
        domainData.subdomains.map((sd, i) => 
          React.createElement(
            'div',
            { 
              key: 'new-' + i,
              className: 'subdomain-item cursor-pointer p-1 hover:bg-gray-700 rounded',
              onClick: () => setSelectedSubdomain(selectedProgram + ':' + sd.code)
            },
            React.createElement(
              'span',
              { className: selectedSubdomain === (selectedProgram + ':' + sd.code) ? 'text-blue-300' : 'text-gray-300' },
              sd.code + ' - ' + sd.description
            )
          )
        )
      );
    });
  };

  return React.createElement(
    'div',
    { className: 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50' },
    React.createElement(
      'div',
      { className: 'bg-gray-800 p-6 rounded-lg w-full max-w-2xl max-h-screen overflow-y-auto' },
      React.createElement('h2', { className: 'text-xl font-bold text-blue-400 mb-4' }, 'Adicionar Subdomínio'),
      // Seletor de Programa
      React.createElement(
        'div',
        { className: 'flex mb-4 border-b border-gray-700' },
        ['ACARE', 'NASA'].map(program => 
          React.createElement(
            'button',
            {
              key: program,
              className: `px-4 py-2 font-semibold ${selectedProgram === program ? 'text-blue-400 border-b-2 border-blue-400' : 'text-gray-400'}`,
              onClick: () => setSelectedProgram(program)
            },
            program
          )
        )
      ),
      // Lista Hierárquica
      React.createElement(
        'div',
        { className: 'mb-4' },
        renderDomainGroups()
      ),
      // Opção Customizada
      React.createElement(
        'div',
        { className: 'mt-4' },
        React.createElement(
          'button',
          {
            className: 'text-blue-400 hover:text-blue-300 flex items-center',
            onClick: () => {
              const customCode = prompt('Digite o código do subdomínio personalizado:');
              if (customCode) {
                const description = prompt('Digite a descrição:');
                if (description) {
                  setSelectedSubdomain(selectedProgram + ':custom:' + customCode + '.' + description);
                }
              }
            }
          },
          React.createElement('span', { className: 'mr-2' }, '➕'),
          'Criar Subdomínio Personalizado'
        )
      ),
      // Botões de Ação
      React.createElement(
        'div',
        { className: 'flex justify-end gap-2 mt-4' },
        React.createElement(
          'button',
          {
            className: 'bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600',
            onClick: onClose
          },
          'Cancelar'
        ),
        React.createElement(
          'button',
          {
            className: 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600',
            onClick: () => {
              onSubmit(selectedSubdomain);
              onClose();
            }
          },
          'Adicionar'
        )
      )
    )
  );
};

// Componente Principal
const App = () => {
  const [data, setData] = React.useState([]);
  const [displayData, setDisplayData] = React.useState([]);
  const [selectedPrograma, setSelectedPrograma] = React.useState('');
  const [loading, setLoading] = React.useState(true);
  const [feedbacks, setFeedbacks] = React.useState({});
  const [modalOpen, setModalOpen] = React.useState(false);
  const [currentRowIndex, setCurrentRowIndex] = React.useState(null);
  const [userName, setUserName] = React.useState('');
  const [subdomainModalOpen, setSubdomainModalOpen] = React.useState(false);
  const [subdomainRowIndex, setSubdomainRowIndex] = React.useState(null);
  const [selectedSubdomain, setSelectedSubdomain] = React.useState('');
  const [approvalStatus, setApprovalStatus] = React.useState({});

  React.useEffect(() => {
    const csv = loadFileData('resultados_formatado.csv');
    if (!csv) {
      console.error('No CSV data available');
      setLoading(false);
      return;
    }
    Papa.parse(csv, {
      header: true,
      skipEmptyLines: true,
      transformHeader: (header) => header.trim().replace(/^"|"$/g, ''),
      transform: (value, header) => value.trim().replace(/^"|"$/g, ''),
      complete: (results) => {
        const cleanedData = results.data.map((row, index) => {
          const acare = [
            parseClassification(row['ACARE 1'] || ''),
            parseClassification(row['ACARE 2'] || ''),
            parseClassification(row['ACARE 3'] || '')
          ].filter(c => c).sort((a, b) => b.score - a.score);
          const nasa = [
            parseClassification(row['NASA 1'] || ''),
            parseClassification(row['NASA 2'] || ''),
            parseClassification(row['NASA 3'] || '')
          ].filter(c => c).sort((a, b) => b.score - a.score);
          return {
            ...row,
            acare: acare.length > 0 ? acare : [{ code: 'N/A', description: 'Sem classificação', score: 0 }],
            nasa: nasa.length > 0 ? nasa : [{ code: 'N/A', description: 'Sem classificação', score: 0 }],
            type: 'original',
            approvedClassification: '',
            approvedBy: '',
            approvalTimestamp: '',
            Comentário: row['Comentário'] || '',
            rowId: index
          };
        });
        setData(cleanedData);
        setDisplayData(cleanedData);
        setFeedbacks(cleanedData.reduce((acc, _, i) => ({ ...acc, [i]: [] }), {}));
        setLoading(false);
      },
      error: (err) => {
        console.error('Error parsing CSV:', err);
        setLoading(false);
      }
    });
  }, []);

  const handleApproval = (rowIndex, type, classificationIndex, status) => {
    if (!userName) {
      alert('Por favor, digite seu nome antes de aprovar.');
      return;
    }

    const timestamp = new Date().toISOString().replace('T', ' ').split('.')[0];
    const originalRow = displayData[rowIndex];
    const classification = type === 'acare' ? originalRow.acare[classificationIndex] : originalRow.nasa[classificationIndex];
    
    if (!classification || classification.code === 'N/A') return;

    const newApprovalRow = {
      ...originalRow,
      type: 'approval',
      approvedClassification: `${type.toUpperCase()} ${classificationIndex + 1}: ${classification.code} - ${classification.description} (${classification.score.toFixed(3)}) - ${status}`,
      approvedBy: userName,
      approvalTimestamp: timestamp,
      Comentário: (feedbacks[rowIndex] || []).join('; '),
      acare: originalRow.acare,
      nasa: originalRow.nasa,
      rowId: originalRow.rowId
    };

    setApprovalStatus((prev) => ({
      ...prev,
      [`${rowIndex}-${type}-${classificationIndex}`]: status
    }));

    setDisplayData((prev) => [...prev, newApprovalRow]);
  };

  const handleFeedbackSubmit = (rowIndex, feedback) => {
    setFeedbacks((prev) => ({
      ...prev,
      [rowIndex]: [...(prev[rowIndex] || []), feedback]
    }));
  };

  const handleSubdomainSubmit = (subdomain) => {
    if (!subdomain || subdomainRowIndex === null) return;

    const [program, code, ...rest] = subdomain.split(':');
    let newClassification;

    if (code === 'custom') {
      const [customCode, description] = rest.join(':').split('.');
      newClassification = { 
        code: customCode, 
        description: description, 
        score: 0.0 
      };
    } else {
      for (const domain of Object.values(subdomains[program] || [])) {
        const found = domain.subdomains.find(s => s.code === code);
        if (found) {
          newClassification = found;
          break;
        }
      }
      if (!newClassification) {
        newClassification = { 
          code: code, 
          description: 'Subdomínio personalizado', 
          score: 0.0 
        };
      }
    }

    setDisplayData((prev) => {
      const newData = [...prev];
      const row = newData[subdomainRowIndex];
      
      if (program === 'ACARE') {
        if (!row.acare.some(c => c.code === 'N/A')) {
          row.acare.push(newClassification);
          row.acare.sort((a, b) => b.score - a.score);
        } else {
          row.acare = [newClassification];
        }
      } else {
        if (!row.nasa.some(c => c.code === 'N/A')) {
          row.nasa.push(newClassification);
          row.nasa.sort((a, b) => b.score - a.score);
        } else {
          row.nasa = [newClassification];
        }
      }
      
      return newData;
    });

    setSubdomainModalOpen(false);
    setSelectedSubdomain('');
    setSubdomainRowIndex(null);
  };

  const downloadCSV = () => {
    const cleanedUserName = userName.trim().replace(/[^a-zA-Z0-9]/g, '_') || 'classification';
    const csvData = displayData
      .filter((row) => row.type === 'approval')
      .map((row) => ({
        Programa: row['Programa'],
        Área: row['Área'],
        'Linha de Pesquisa': row['Linha de Pesquisa'],
        Usuário: row.approvedBy,
        'Classificação Aprovada': row.approvedClassification,
        'Aprovado Por': row.approvedBy,
        'Data de Aprovação': row.approvalTimestamp,
        Feedback: row.Comentário,
        'ACARE 1': row.acare[0]?.code && row.acare[0].code !== 'N/A' ? `"${row.acare[0].code}: ${row.acare[0].description} (${row.acare[0].score.toFixed(3)})"` : '',
        'ACARE 2': row.acare[1]?.code && row.acare[1].code !== 'N/A' ? `"${row.acare[1].code}: ${row.acare[1].description} (${row.acare[1].score.toFixed(3)})"` : '',
        'ACARE 3': row.acare[2]?.code && row.acare[2].code !== 'N/A' ? `"${row.acare[2].code}: ${row.acare[2].description} (${row.acare[2].score.toFixed(3)})"` : '',
        'NASA 1': row.nasa[0]?.code && row.nasa[0].code !== 'N/A' ? `"${row.nasa[0].code}: ${row.nasa[0].description} (${row.nasa[0].score.toFixed(3)})"` : '',
        'NASA 2': row.nasa[1]?.code && row.nasa[1].code !== 'N/A' ? `"${row.nasa[1].code}: ${row.nasa[1].description} (${row.nasa[1].score.toFixed(3)})"` : '',
        'NASA 3': row.nasa[2]?.code && row.nasa[2].code !== 'N/A' ? `"${row.nasa[2].code}: ${row.nasa[2].description} (${row.nasa[2].score.toFixed(3)})"` : ''
      }));

    const csv = Papa.unparse(csvData, {
      quotes: true,
      delimiter: ',',
      header: true
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${cleanedUserName}_classification_approval.csv`;
    link.click();
    URL.revokeObjectURL(url);
  };

  const programs = [...new Set(data.map((row) => row['Programa']).filter(p => p))];

  if (loading) {
    return React.createElement(
      'div',
      { className: 'flex items-center justify-center h-screen' },
      React.createElement('p', { className: 'text-xl text-blue-400' }, 'Carregando...')
    );
  }

  return React.createElement(
    'div',
    { className: 'container mx-auto p-4' },
    React.createElement(InstructionPanel, null),
    React.createElement(
      'div',
      { className: 'mb-4' },
      React.createElement('label', { className: 'block text-gray-300 font-semibold mb-2' }, 'Digite Seu Nome:'),
      React.createElement('input', {
        type: 'text',
        className: 'w-full sm:w-1/4 p-2 bg-gray-800 border border-gray-700 rounded-lg text-white',
        placeholder: 'Seu nome',
        value: userName,
        onChange: (e) => setUserName(e.target.value)
      })
    ),
    React.createElement(
      'div',
      { className: 'mb-6 flex gap-2' },
      React.createElement(
        'select',
        {
          className: 'w-full sm:w-1/4 p-2 bg-gray-800 border border-gray-700 rounded-lg text-white',
          value: selectedPrograma,
          onChange: (e) => setSelectedPrograma(e.target.value)
        },
        React.createElement('option', { value: '' }, 'Todos os Programas'),
        programs.map((program) =>
          React.createElement('option', { key: program, value: program }, (() => {
            const [name, acronym] = program.split(' – ').map(s => s.trim());
            return acronym ? `${acronym} - ${name}` : program;
          })())
        )
      )
    ),
    React.createElement(
      'div',
      { className: 'flex flex-col sm:flex-row justify-between items-center mb-2' },
      React.createElement(
        'div',
        { className: 'flex flex-col sm:flex-row gap-2' },
        React.createElement('img', {
          src: 'https://sc2c.ufsc.br/aero/wp-content/uploads/2025/04/logo_sc2c_horizontal.png',
          alt: 'SC2C Aero Logo',
          className: 'h-12 sm:h-16'
        }),
        React.createElement('h1', { className: 'text-2xl sm:text-3xl font-bold text-blue-400' }, 'Módulo de Aprovação')
      ),
      React.createElement(
        'div',
        { className: 'flex flex-col sm:flex-row items-center gap-2' },
        React.createElement(
          'button',
          {
            className: 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 active:scale-95 active:shadow-inner transition-transform',
            onClick: downloadCSV
          },
          'Baixar CSV Atualizado'
        ),
        React.createElement(
          'a',
          {
            href: 'https://www.daccampania.com/wp-content/uploads/2022/01/ACARE_Taxonomy.pdf',
            target: '_blank',
            rel: 'noopener noreferrer',
            className: 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 active:scale-95 active:shadow-inner transition-transform'
          },
          'Manual ACARE'
        ),
        React.createElement(
          'a',
          {
            href: 'https://www3.nasa.gov/sites/default/files/atoms/files/2020_nasa_technology_taxonomy.pdf',
            target: '_blank',
            rel: 'noopener noreferrer',
            className: 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 active:scale-95 active:shadow-inner transition-transform'
          },
          'Manual NASA'
        )
      )
    ),
    React.createElement(
      'div',
      { className: 'bg-gray-800 shadow-md rounded-lg overflow-x-auto' },
      React.createElement(
        'table',
        { className: 'min-w-full divide-y divide-gray-700' },
        React.createElement(
          'thead',
          { className: 'bg-blue-800 text-white' },
          React.createElement(
            'tr',
            null,
            React.createElement('th', { className: 'px-4 py-2 text-left text-sm sm:text-base' }, 'Área'),
            React.createElement('th', { className: 'px-4 py-2 text-left text-sm sm:text-base' }, 'Linha de Pesquisa'),
            React.createElement('th', { className: 'px-4 py-2 text-left text-sm sm:text-base' }, 'Classificações ACARE'),
            React.createElement('th', { className: 'px-4 py-2 text-left text-sm sm:text-base' }, 'Classificações NASA'),
            React.createElement('th', { className: 'px-4 py-2 text-left text-sm sm:text-base' }, 'Feedback'),
            React.createElement('th', { className: 'px-4 py-2 text-left text-sm sm:text-base' }, 'Adicionar Subdomínio')
          )
        ),
        React.createElement(
          'tbody',
          { className: 'divide-y divide-gray-700' },
          displayData
            .filter((row) => row.type === 'original' && (!selectedPrograma || row['Programa'] === selectedPrograma))
            .map((row, rowIndex) =>
              React.createElement(
                'tr',
                { key: row.rowId },
                React.createElement('td', { className: 'px-4 py-2 text-sm sm:text-base' }, row['Área'] || 'N/A'),
                React.createElement('td', { className: 'px-4 py-2 text-sm sm:text-base' }, row['Linha de Pesquisa'] || 'N/A'),
                React.createElement(
                  'td',
                  { className: 'px-4 py-2' },
                  React.createElement(
                    'ul',
                    { className: 'list-disc list-inside text-sm sm:text-base' },
                    Array(3).fill().map((_, i) => {
                      const c = row.acare[i];
                      const status = approvalStatus[`${rowIndex}-acare-${i}`];
                      return React.createElement(
                        'li',
                        { key: i },
                        c && c.code !== 'N/A' ? `${c.code}: ${c.description} (${c.score.toFixed(3)})` : `Sugestão ${i + 1}: Sem classificação`,
                        c && c.code !== 'N/A' && React.createElement(
                          'div',
                          { className: 'mt-1 mb-2' },
                          React.createElement(
                            'button',
                            {
                              className: `bg-green-500 text-white px-2 py-1 rounded mr-2 hover:bg-green-600 active:scale-95 active:shadow-inner transition-transform text-xs sm:text-sm ${status === 'Approved' ? 'opacity-50 cursor-not-allowed' : ''}`,
                              onClick: () => handleApproval(rowIndex, 'acare', i, 'Approved'),
                              disabled: status === 'Approved'
                            },
                            'Aprovar'
                          ),
                          React.createElement(
                            'button',
                            {
                              className: `bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 active:scale-95 active:shadow-inner transition-transform text-xs sm:text-sm ${status === 'Rejected' ? 'opacity-50 cursor-not-allowed' : ''}`,
                              onClick: () => handleApproval(rowIndex, 'acare', i, 'Rejected'),
                              disabled: status === 'Rejected'
                            },
                            'Rejeitar'
                          )
                        )
                      );
                    }),
                    row.acare.some(c => c && c.code !== 'N/A') && React.createElement(
                      'div',
                      { className: 'flex justify-center mt-2' },
                      React.createElement(
                        Recharts.ResponsiveContainer,
                        { width: '80%', height: 100 },
                        React.createElement(
                          Recharts.BarChart,
                          { data: row.acare.filter(c => c && c.code !== 'N/A') },
                          React.createElement(Recharts.CartesianGrid, { strokeDasharray: '3 3', stroke: '#4b5563' }),
                          React.createElement(Recharts.XAxis, { dataKey: 'code', fontSize: 12, stroke: '#ffffff' }),
                          React.createElement(Recharts.YAxis, { fontSize: 12, stroke: '#ffffff' }),
                          React.createElement(Recharts.Tooltip, {
                            formatter: (value, name, props) => [value.toFixed(3), props.payload.description]
                          }),
                          React.createElement(
                            Recharts.Bar,
                            { dataKey: 'score' },
                            row.acare.filter(c => c && c.code !== 'N/A').map((entry, index) =>
                              React.createElement(Recharts.Cell, {
                                key: `cell-${index}`,
                                fill: approvalStatus[`${rowIndex}-acare-${index}`] === 'Approved'
                                  ? '#22c55e'
                                  : approvalStatus[`${rowIndex}-acare-${index}`] === 'Rejected'
                                  ? '#ef4444'
                                  : '#3b82f6'
                              })
                            )
                          )
                        )
                      )
                    )
                  )
                ),
                React.createElement(
                  'td',
                  { className: 'px-4 py-2' },
                  React.createElement(
                    'ul',
                    { className: 'list-disc list-inside text-sm sm:text-base' },
                    Array(3).fill().map((_, i) => {
                      const c = row.nasa[i];
                      const status = approvalStatus[`${rowIndex}-nasa-${i}`];
                      return React.createElement(
                        'li',
                        { key: i },
                        c && c.code !== 'N/A' ? `${c.code}: ${c.description} (${c.score.toFixed(3)})` : `Sugestão ${i + 1}: Sem classificação`,
                        c && c.code !== 'N/A' && React.createElement(
                          'div',
                          { className: 'mt-1 mb-2' },
                          React.createElement(
                            'button',
                            {
                              className: `bg-green-500 text-white px-2 py-1 rounded mr-2 hover:bg-green-600 active:scale-95 active:shadow-inner transition-transform text-xs sm:text-sm ${status === 'Approved' ? 'opacity-50 cursor-not-allowed' : ''}`,
                              onClick: () => handleApproval(rowIndex, 'nasa', i, 'Approved'),
                              disabled: status === 'Approved'
                            },
                            'Aprovar'
                          ),
                          React.createElement(
                            'button',
                            {
                              className: `bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 active:scale-95 active:shadow-inner transition-transform text-xs sm:text-sm ${status === 'Rejected' ? 'opacity-50 cursor-not-allowed' : ''}`,
                              onClick: () => handleApproval(rowIndex, 'nasa', i, 'Rejected'),
                              disabled: status === 'Rejected'
                            },
                            'Rejeitar'
                          )
                        )
                      );
                    }),
                    row.nasa.some(c => c && c.code !== 'N/A') && React.createElement(
                      'div',
                      { className: 'flex justify-center mt-2' },
                      React.createElement(
                        Recharts.ResponsiveContainer,
                        { width: '80%', height: 100 },
                        React.createElement(
                          Recharts.BarChart,
                          { data: row.nasa.filter(c => c && c.code !== 'N/A') },
                          React.createElement(Recharts.CartesianGrid, { strokeDasharray: '3 3', stroke: '#4b5563' }),
                          React.createElement(Recharts.XAxis, { dataKey: 'code', fontSize: 12, stroke: '#ffffff' }),
                          React.createElement(Recharts.YAxis, { fontSize: 12, stroke: '#ffffff' }),
                          React.createElement(Recharts.Tooltip, {
                            formatter: (value, name, props) => [value.toFixed(3), props.payload.description]
                          }),
                          React.createElement(
                            Recharts.Bar,
                            { dataKey: 'score' },
                            row.nasa.filter(c => c && c.code !== 'N/A').map((entry, index) =>
                              React.createElement(Recharts.Cell, {
                                key: `cell-${index}`,
                                fill: approvalStatus[`${rowIndex}-nasa-${index}`] === 'Approved'
                                  ? '#22c55e'
                                  : approvalStatus[`${rowIndex}-nasa-${index}`] === 'Rejected'
                                  ? '#ef4444'
                                  : '#3b82f6'
                              })
                            )
                          )
                        )
                      )
                    )
                  )
                ),
                React.createElement(
                  'td',
                  { className: 'px-4 py-2 text-sm sm:text-base' },
                  React.createElement(
                    'button',
                    {
                      className: 'bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 active:scale-95 active:shadow-inner transition-transform text-xs sm:text-sm',
                      onClick: () => {
                        setCurrentRowIndex(rowIndex);
                        setModalOpen(true);
                      }
                    },
                    'Ver Feedback'
                  )
                ),
                React.createElement(
                  'td',
                  { className: 'px-4 py-2' },
                  React.createElement(
                    'button',
                    {
                      className: 'bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 active:scale-95 active:shadow-inner transition-transform text-xs sm:text-sm',
                      onClick: () => {
                        setSubdomainRowIndex(rowIndex);
                        setSubdomainModalOpen(true);
                      }
                    },
                    'Adicionar Subdomínio'
                  )
                )
              )
            )
        )
      )
    ),
    React.createElement(FeedbackModal, {
      isOpen: modalOpen,
      onClose: () => setModalOpen(false),
      feedback: feedbacks[currentRowIndex] || [],
      onSubmit: (feedback) => handleFeedbackSubmit(currentRowIndex, feedback),
      rowIndex: currentRowIndex
    }),
    React.createElement(SubdomainModal, {
      isOpen: subdomainModalOpen,
      onClose: () => setSubdomainModalOpen(false),
      onSubmit: handleSubdomainSubmit,
      rowData: displayData[subdomainRowIndex] || {}
    })
  );
};
// Exemplo de como implementar a tabela com header fixo no seu approval_module.js
// Adapte este código para sua estrutura existente

// Componente da tabela com header fixo
function createTableWithStickyHeader(data, headers) {
  var tableContainer = React.createElement('div', {
    className: 'table-container relative'
  }, [
    // Indicador visual do header fixo
    React.createElement('div', {
      key: 'indicator',
      className: 'sticky-indicator'
    }),
    
    React.createElement('table', {
      key: 'table',
      className: 'w-full text-sm'
    }, [
      // Header fixo
      React.createElement('thead', {
        key: 'thead',
        className: 'sticky-header'
      }, 
        React.createElement('tr', {
          key: 'header-row'
        }, headers.map(function(header, index) {
          return React.createElement('th', {
            key: 'header-' + index,
            className: 'px-3 py-3 text-left font-semibold text-gray-200'
          }, header);
        }))
      ),
      
      // Corpo da tabela
      React.createElement('tbody', {
        key: 'tbody',
        className: 'table-body'
      }, data.map(function(row, rowIndex) {
        return React.createElement('tr', {
          key: 'row-' + rowIndex,
          className: 'hover:bg-gray-700 transition-colors duration-200'
        }, row.map(function(cell, cellIndex) {
          return React.createElement('td', {
            key: 'cell-' + rowIndex + '-' + cellIndex,
            className: 'px-3 py-2 border-b border-gray-600'
          }, cell || '');
        }));
      }))
    ])
  ]);
  
  return tableContainer;
}

// Exemplo de uso da tabela (adapte para seus dados)
function renderDataTable(csvData) {
  if (!csvData || csvData.length === 0) {
    return React.createElement('div', {
      className: 'text-center py-8 text-gray-400'
    }, 'Nenhum dado encontrado');
  }
  
  // Assumindo que a primeira linha são os cabeçalhos
  var headers = csvData[0];
  var rows = csvData.slice(1);
  
  return React.createElement('div', {
    className: 'mb-8'
  }, [
    React.createElement('h2', {
      key: 'title',
      className: 'text-xl font-bold mb-4 text-gray-200'
    }, 'Dados da Pesquisa'),
    
    createTableWithStickyHeader(rows, headers)
  ]);
}

// Função para processar CSV (mantenha sua lógica existente e adapte)
function processCSVData() {
  var csvContent = loadFileData('resultados_formatado.csv');
  if (!csvContent) {
    return [];
  }
  
  try {
    // Decodificar conteúdo que foi processado pelo addslashes() do PHP
    csvContent = csvContent.replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, "\\");
    
    var parsedData = Papa.parse(csvContent, {
      header: false,
      skipEmptyLines: true,
      delimiter: ',',
      quoteChar: '"'
    });
    
    return parsedData.data || [];
  } catch (error) {
    console.error('Erro ao processar CSV:', error);
    return [];
  }
}

// Componente principal (adapte para sua estrutura)
function ApprovalModule() {
  var csvData = processCSVData();
  
  return React.createElement('div', {
    className: 'min-h-screen bg-gray-900 p-4'
  }, [
    React.createElement('header', {
      key: 'header',
      className: 'mb-8'
    }, 
      React.createElement('h1', {
        className: 'text-3xl font-bold text-center text-blue-400'
      }, 'SC2C.Aero - Módulo de Aprovação')
    ),
    
    React.createElement('main', {
      key: 'main',
      className: 'max-w-7xl mx-auto'
    }, [
      renderDataTable(csvData)
    ])
  ]);
}

// Renderizar o componente principal
ReactDOM.render(
  React.createElement(ApprovalModule),
  document.getElementById('root')
);

// Função utilitária para atualizar a tabela dinamicamente (se necessário)
function updateTable(newData) {
  if (newData && newData.length > 0) {
    ReactDOM.render(
      React.createElement(ApprovalModule),
      document.getElementById('root')
    );
  }
}

// Função para redimensionar colunas automaticamente (opcional)
function autoResizeColumns() {
  setTimeout(function() {
    var table = document.querySelector('.table-container table');
    if (table) {
      var cells = table.querySelectorAll('th, td');
      for (var i = 0; i < cells.length; i++) {
        var cell = cells[i];
        cell.style.minWidth = '100px';
        cell.style.maxWidth = '200px';
        cell.style.wordWrap = 'break-word';
      }
    }
  }, 100);
}

// Inicializar redimensionamento automático
document.addEventListener('DOMContentLoaded', autoResizeColumns);

// Re-aplicar após atualizações do React
var originalRender = ReactDOM.render;
ReactDOM.render = function() {
  var result = originalRender.apply(this, arguments);
  autoResizeColumns();
  return result;
};

// Função para exportar dados (funcionalidade adicional)
function exportTableData() {
  var csvData = processCSVData();
  if (csvData.length === 0) {
    alert('Nenhum dado para exportar');
    return;
  }
  
  var csvContent = csvData.map(function(row) {
    return row.map(function(cell) {
      return '"' + (cell || '').replace(/"/g, '""') + '"';
    }).join(',');
  }).join('\n');
  
  var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'dados_exportados.csv';
  link.click();
}

// Função para filtrar dados (funcionalidade adicional)
function filterTableData(searchTerm) {
  var csvData = processCSVData();
  if (!searchTerm || searchTerm.trim() === '') {
    return csvData;
  }
  
  var headers = csvData[0] || [];
  var filteredRows = csvData.slice(1).filter(function(row) {
    return row.some(function(cell) {
      return (cell || '').toString().toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1;
    });
  });
  
  return [headers].concat(filteredRows);
}

// Evento para busca em tempo real (se você adicionar um campo de busca)
function handleSearch(event) {
  var searchTerm = event.target.value;
  var filteredData = filterTableData(searchTerm);
  // Aqui você atualizaria o estado da tabela com os dados filtrados
  // Implemente conforme sua estrutura de estado
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(React.createElement(App));