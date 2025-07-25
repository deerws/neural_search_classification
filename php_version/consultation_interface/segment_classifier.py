
import pandas as pd
import re
import numpy as np

# Function to normalize domain names for comparison
def normalize_domain(domain):
    # Check if domain is a string and not NaN
    if isinstance(domain, str) and not pd.isna(domain):
        # Remove leading numbers and dots (e.g., "18.Smart Materials" -> "Smart Materials")
        domain = re.sub(r'^\d+\.', '', domain).strip()
        # Convert to lowercase and remove extra spaces
        domain = ' '.join(domain.split()).lower()
        return domain
    return ''  # Return empty string for non-string or NaN values

# Define the domain-to-segment mapping
segment_mapping = {
    'ACARE': {
        'FLP': {
            'name': 'Flight Physics (FLP)',
            'domains': [
                'computational fluid dynamics',
                'unsteady aerodynamics',
                'airflow control',
                'high lift devices',
                'wing design',
                'aerodynamics of external/removable items',
                'wind tunnel testing/technology',
                'wind tunnel measuring techniques',
                'computational acoustics',
                'external noise prediction'
            ]
        },
        'AST': {
            'name': 'Aerostructures (AST)',
            'domains': [
                'metallic materials & basic processes',
                'non-metallic materials & basic processes',
                'composite materials & basic processes',
                'advanced manufacturing processes & technologies',
                'structural analysis and design',
                'aero-elasticity',
                'buckling, vibrations, and acoustics',
                'smart materials and structures',
                'internal noise prediction',
                'helicopter aero-acoustics',
                'noise reduction',
                'acoustic measurements and test technology',
                'aircraft security'
            ]
        },
        'PRO': {
            'name': 'Propulsion (PRO)',
            'domains': [
                'performance',
                'turbomachinery/propulsion aerodynamics',
                'combustion',
                'air-breathing propulsion',
                'heat transfer',
                'nozzles, vectored thrust, reheat',
                'engine controls',
                'auxiliary power unit',
                'fuels and lubricants',
                'test bench calibration',
                'engine health monitoring',
                'experimental facilities & measurement techniques',
                'computational methods',
                'emissions pollution',
                'hydrogen in aviation',
                'hybrid electric flight'
            ]
        },
        'AVS': {
            'name': 'Aircraft Avionics, Systems & Equipment (AVS)',
            'domains': [
                'avionics',
                'cockpit systems, visualization & display systems',
                'navigation/flight management/autoland',
                'warning systems',
                'electronics & microelectronics for on-board systems',
                'sensors integration',
                'flight data/recording',
                'communications systems',
                'identification',
                'avionics integration',
                'optics, optronics, lasers, image processing',
                'electronic library system',
                'aircraft health & usage monitoring system',
                'smart maintenance systems',
                'lighting systems',
                'aircraft security',
                'electrical power generation, distribution & actuation',
                'pneumatic systems',
                'passenger and freight systems',
                'environmental control system',
                'water and waste systems',
                'fuel systems',
                'landing gear and braking systems',
                'fire protection systems',
                'hydraulic systems'
            ]
        },
        'FLM': {
            'name': 'Flight Mechanics (FLM)',
            'domains': [
                'open-loop aircraft stability analysis',
                'flight control system',
                'aircraft performance analysis',
                'optimization of aircraft performance',
                'system failure and damage analysis',
                'environmental hazard analysis'
            ]
        },
        'IDV': {
            'name': 'Integrated Design & Validation (IDV)',
            'domains': [
                'methods and it tools for collaborative engineering',
                'on-board systems engineering',
                'environmental and em compliance',
                'flight/ground tests',
                'life-cycle integration',
                'system certification',
                'fault tolerant systems',
                'hazard analysis',
                'safety modelling',
                'air safety data analysis',
                'system reliability',
                'security/risk analysis',
                'maintenance modelling',
                'infra-red and radar signature control',
                'advanced information processing',
                'collaborative decision making',
                'simulator environments & virtual reality',
                'decision support systems',
                'information & knowledge management',
                'autonomous operation',
                'aeronautical software engineering',
                'development of operational research methods & tools',
                'synthetic environment & virtual reality tools',
                'aircraft performance assessment',
                'airport performance assessment',
                'business modelling',
                'numerical models (including fast time simulation)',
                'real time simulators',
                'general purpose equipment',
                'reference data for r&d use and live/rt data use',
                'methodology',
                'large scale validation experiments/platforms',
                'ecodesign and engineering for sustainability'
            ]
        },
        'AOP': {
            'name': 'Aircraft Operations (AOP)',
            'domains': [
                'air traffic management',
                'airports',
                'maintenance, repair & overhaul (mro)'
            ]
        },
        'UAS': {
            'name': 'Unmanned Aerial Systems (UAS)',
            'domains': [
                'uas & scaled flight testing'
            ]
        },
        'HFA': {
            'name': 'Human Factors (HFA)',
            'domains': [
                'human factors integration, man-machine interface',
                'human information processing',
                'human performance modelling & enhancement',
                'selection & training',
                'human survivability, protection & stress effects',
                'human element in security'
            ]
        },
        'ICS': {
            'name': 'Innovative Concepts & Scenarios (ICS)',
            'domains': [
                'scenarios analysis',
                'unconventional configurations & new aircraft concepts',
                'breakthrough technologies',
                'industry 4.0 to industry 5.0'
            ]
        }
    },
    'NASA': {
        'PS': {
            'name': 'Propulsion Systems',
            'domains': [
                'chemical propulsion',
                'electric space propulsion',
                'aero propulsion',
                'advanced propulsion'
            ]
        },
        'FCA': {
            'name': 'Flight Computing & Avionics',
            'domains': [
                'avionics component technologies',
                'avionics systems & subsystems',
                'avionics tools, models, and analysis',
                'fca.1',
                'fca.2'
            ]
        },
        'APES': {
            'name': 'Aerospace Power & Energy Storage',
            'domains': [
                'power generation & energy conversion',
                'energy storage',
                'power management and distribution'
            ]
        },
        'RS': {
            'name': 'Robotic Systems',
            'domains': [
                'sensing & perception',
                'mobility',
                'manipulation',
                'human-robot interaction',
                'autonomous rendezvous & docking',
                'robotics integration'
            ]
        },
        'CNDT': {
            'name': 'Communications, Navigation & Debris Tracking',
            'domains': [
                'optical communications',
                'radio frequency',
                'internetworking',
                'network provided position/navigation/timing',
                'revolutionary communications technologies',
                'ground-based debris tracking & management',
                'acoustic communications'
            ]
        },
        'HLSH': {
            'name': 'Human Health, Life Support & Habitation',
            'domains': [
                'environmental control & life support',
                'extravehicular activity systems',
                'human health and performance',
                'environmental monitoring & safety',
                'radiation',
                'human systems integration'
            ]
        },
        'EDS': {
            'name': 'Exploration Destination Systems',
            'domains': [
                'in-situ resource utilization',
                'mission infrastructure, sustainability & support',
                'mission operations & safety'
            ]
        },
        'SI': {
            'name': 'Sensors & Instruments',
            'domains': [
                'remote sensing instruments',
                'observatories',
                'in-situ instruments'
            ]
        },
        'EDL': {
            'name': 'Entry, Descent & Landing',
            'domains': [
                'aeroassist & atmospheric entry',
                'descent',
                'landing',
                'vehicle systems'
            ]
        },
        'AS': {
            'name': 'Autonomous Systems',
            'domains': [
                'situational and self awareness',
                'reasoning and acting',
                'collaboration & interaction',
                'engineering & integrity'
            ]
        },
        'SMSIP': {
            'name': 'Software, Modeling, Simulation & Information Processing',
            'domains': [
                'software development, engineering and integrity',
                'modeling',
                'simulation',
                'information processing',
                'mission architecture, system analysis, and concept development',
                'ground computing'
            ]
        },
        'MSMM': {
            'name': 'Materials, Structures, Mechanical Systems & Manufacturing',
            'domains': [
                'materials',
                'structures',
                'mechanical systems',
                'manufacturing',
                'structural dynamics'
            ]
        },
        'GTSS': {
            'name': 'Ground, Test & Surface Systems',
            'domains': [
                'infrastructure optimization',
                'test & qualification',
                'assembly, integration & launch',
                'mission success technologies'
            ]
        },
        'TMS': {
            'name': 'Thermal Management Systems',
            'domains': [
                'cryogenic systems',
                'thermal control components and systems',
                'thermal protection components'
            ]
        },
        'FVS': {
            'name': 'Flight Vehicle Systems',
            'domains': [
                'aerosciences',
                'flight mechanics'
            ]
        },
        'ATMRT': {
            'name': 'Air Traffic Management & Range Tracking',
            'domains': [
                'safe all vehicle access',
                'weather/environment',
                'traffic management concepts',
                'architectures & infrastructure',
                'tracking, surveillance & flight safety',
                'integrated modeling, simulation & testing'
            ]
        },
        'GNC': {
            'name': 'Guidance, Navigation & Control (GN&C)',
            'domains': [
                'guidance & targeting algorithms',
                'navigation technologies',
                'control technologies',
                'attitude estimation technologies',
                'gn&c systems engineering',
                'trajectory generation & optimization for airspace'
            ]
        }
    }
}

# Function to get segment name based on domain and classification
def get_segmento(domain, classification):
    # Handle NaN or non-string classification
    if not isinstance(classification, str) or pd.isna(classification):
        return ''
    
    # Determine classification type (ACARE or NASA)
    class_type = 'ACARE' if 'ACARE' in classification else 'NASA'
    
    # Normalize domain
    normalized_domain = normalize_domain(domain)
    
    # Find the segment for the given domain
    if normalized_domain:  # Only process if normalized_domain is not empty
        for segment_code, segment_info in segment_mapping[class_type].items():
            if normalized_domain in segment_info['domains']:
                return segment_info['name']
    return ''  # Return empty string if no match found or domain is invalid

# Read the input CSV
input_file = 'aprovacoes_consolidadas_2025-07-24_16-15-35.csv'
df = pd.read_csv(input_file, sep=';', encoding='utf-8-sig')

# Fill the Segmento column and track unmapped domains
unmapped_domains = set()
df['Segmento'] = df.apply(
    lambda row: get_segmento(row['Domínio'], row['Classificação']) or (
        unmapped_domains.add((str(row['Domínio']), str(row['Classificação']))), '')[1],
    axis=1
)

# Save the updated DataFrame to a new CSV
output_file = 'final_aprovações_BACKUP_2_updated.csv'
df.to_csv(output_file, sep=';', encoding='utf-8-sig', index=False)

# Save unmapped domains to a log file
with open('unmapped_domains.log', 'w', encoding='utf-8') as f:
    f.write("Domínios não mapeados:\n")
    for domain, classification in unmapped_domains:
        f.write(f"Domínio: {domain}, Classificação: {classification}\n")

print(f"Updated CSV file saved as {output_file}")
print(f"Unmapped domains logged to unmapped_domains.log")
