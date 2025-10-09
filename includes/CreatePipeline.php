<?php
/**
 * Pipeline Creation Service for Structured Data Plugin
 * 
 * Handles the creation of Data Machine pipelines, flows, and steps for structured data analysis.
 * Provides a clean service interface for pipeline creation operations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class DM_StructuredData_CreatePipeline {
    
    /**
     * Create the structured data analysis pipeline using Data Machine's enhanced filter.
     *
     * Creates a complete pipeline with fetch, AI, and update steps configured
     * for WordPress content analysis and structured data enhancement.
     *
     * @return array Success response with pipeline details or error information
     */
    public function create_pipeline(): array {
        try {
            // Check Data Machine dependency
            if (!has_filter('dm_create_pipeline')) {
                return [
                    'success' => false,
                    'error' => 'Data Machine plugin is required for this plugin to work.'
                ];
            }

            // Use Data Machine's enhanced complete pipeline creation
            $pipeline_data = [
                'pipeline_name' => 'Structured Data Analysis Pipeline',
                'steps' => [
                    [
                        'step_type' => 'fetch',
                        'execution_order' => 0,
                        'label' => 'WordPress Fetch',
                        'handler' => 'wordpress_posts',
                        'handler_config' => [
                            'post_type' => 'any',
                            'post_status' => 'any',
                            'post_id' => 0
                        ]
                    ],
                    [
                        'step_type' => 'ai',
                        'execution_order' => 1,
                        'label' => 'AI Analysis',
                        'provider' => 'openai',
                        'model' => 'gpt-5-mini',
                        'system_prompt' => 'You are an AI assistant that analyzes WordPress content to extract semantic metadata for structured data enhancement. Analyze the content and provide semantic classifications including content_type, audience_level, skill_prerequisites, content_characteristics, primary_intent, actionability, complexity_score, and estimated_completion_time.'
                    ],
                    [
                        'step_type' => 'update',
                        'execution_order' => 2,
                        'label' => 'Update Post Metadata',
                        'handler' => 'structured_data',
                        'handler_config' => []
                    ]
                ],
                'flow_config' => [
                    'flow_name' => 'Structured Data Analysis Flow',
                    'scheduling_config' => ['interval' => 'manual']
                ]
            ];

            // Create complete pipeline using Data Machine's unified system
            $pipeline_id = apply_filters('dm_create_pipeline', null, $pipeline_data);

            if (!$pipeline_id) {
                return [
                    'success' => false,
                    'error' => 'Failed to create pipeline using Data Machine system.'
                ];
            }

            // Get the created flow ID
            $flows = apply_filters('dm_get_pipeline_flows', [], $pipeline_id);
            $flow_id = !empty($flows) ? $flows[0]['flow_id'] : null;

            if (!$flow_id) {
                return [
                    'success' => false,
                    'error' => 'Pipeline created but flow not found.'
                ];
            }

            // Store IDs for plugin reference
            update_option('dm_structured_data_pipeline_id', $pipeline_id);
            update_option('dm_structured_data_flow_id', $flow_id);

            return [
                'success' => true,
                'message' => 'Pipeline created successfully using Data Machine unified system!',
                'pipeline_id' => $pipeline_id,
                'flow_id' => $flow_id
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create pipeline: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if the structured data pipeline already exists.
     * 
     * @return bool True if pipeline exists, false otherwise
     */
    public function pipeline_exists(): bool {
        $pipelines = apply_filters('dm_get_pipelines', []);
        foreach ($pipelines as $pipeline) {
            if ($pipeline['pipeline_name'] === 'Structured Data Analysis Pipeline') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get the flow step ID for a specific step type in the structured data flow.
     * 
     * @param string $step_type Step type to find (fetch, ai, publish)
     * @return string|null Flow step ID or null if not found
     */
    public function get_flow_step_id(string $step_type): ?string {
        $flow_id = get_option('dm_structured_data_flow_id');
        if (!$flow_id) {
            return null;
        }
        
        // Get database services
        $all_databases = apply_filters('dm_db', []);
        $db_flows = $all_databases['flows'] ?? null;
        
        if (!$db_flows) {
            return null;
        }
        
        $flow = $db_flows->get_flow($flow_id);
        if (!$flow || !isset($flow['flow_config'])) {
            return null;
        }
        
        // Find flow step with matching step_type
        foreach ($flow['flow_config'] as $flow_step_id => $step_config) {
            if (isset($step_config['step_type']) && $step_config['step_type'] === $step_type) {
                return $flow_step_id;
            }
        }
        
        return null;
    }
}