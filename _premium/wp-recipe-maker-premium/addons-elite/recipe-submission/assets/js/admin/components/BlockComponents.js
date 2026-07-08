import React, { Component } from 'react';

import AgreeBlock from './blocks/AgreeBlock';
import CustomTaxonomyBlock from './blocks/CustomTaxonomyBlock';
import CustomFieldBlock from './blocks/CustomFieldBlock';
import FileUploadBlock from './blocks/FileUploadBlock';
import HeaderBlock from './blocks/HeaderBlock';
import HtmlBlock from './blocks/HtmlBlock';
import InputBlock from './blocks/InputBlock';
import ParagraphBlock from './blocks/ParagraphBlock';
// import RecipeImageBlock from './blocks/RecipeImageBlock';
import SubmitBlock from './blocks/SubmitBlock';
import TextareaBlock from './blocks/TextareaBlock';

const blockComponents = {
    header: HeaderBlock,
    paragraph: ParagraphBlock,
    html: HtmlBlock,
    agree_to_terms: AgreeBlock,
    submit: SubmitBlock,
    recipe_name: InputBlock,
    recipe_summary: TextareaBlock,
    recipe_image: FileUploadBlock,
    recipe_video_upload: FileUploadBlock,
    recipe_video_embed: InputBlock,
    recipe_servings: InputBlock,
    recipe_prep_time: InputBlock,
    recipe_cook_time: InputBlock,
    recipe_total_time: InputBlock,
    recipe_cost: InputBlock,
    recipe_courses: InputBlock,
    recipe_cuisines: InputBlock,
    recipe_equipment: TextareaBlock,
    recipe_ingredients: TextareaBlock,
    recipe_instructions: TextareaBlock,
    recipe_notes: TextareaBlock,
    recipe_custom_taxonomy: CustomTaxonomyBlock,
    recipe_custom_field: CustomFieldBlock,
    user_name: InputBlock,
    user_email: InputBlock,
};

export default blockComponents;